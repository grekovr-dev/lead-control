<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\GetVisitorAcquisitionFunnelReportQuery;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\VisitorAcquisitionFunnelReportReadModel;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\VisitorAcquisitionFunnelReportRowView;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\VisitorAcquisitionFunnelReportView;
use Inbound\Domain\Shared\DateRange;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;

final class EloquentVisitorAcquisitionFunnelReportReadModel implements VisitorAcquisitionFunnelReportReadModel
{
    public function __invoke(GetVisitorAcquisitionFunnelReportQuery $query): VisitorAcquisitionFunnelReportView
    {
        $visitorCounts = $this->fetchVisitorCounts($query->firstVisitPeriod);
        $visitorsCount = $this->countVisitors($query->firstVisitPeriod);
        $leadCounts = $this->fetchLeadCounts($query->firstVisitPeriod);
        $leadsCount = array_sum(array_column($leadCounts, 'count'));

        /** @var array<string, array{
         *     visitorAttributionSource: ?string,
         *     visitorAttributionMedium: ?string,
         *     visitorAttributionCampaign: ?string,
         *     visitorsCount: int,
         *     leadsCount: int
         * }>
         */
        $rows = [];

        $this->mergeCounts($rows, $visitorCounts, 'visitorsCount');
        $this->mergeCounts($rows, $leadCounts, 'leadsCount');

        return new VisitorAcquisitionFunnelReportView(
            visitorsCount: $visitorsCount,
            leadsCount: $leadsCount,
            visitorsToLeadsConversionRate: $this->calculateConversionRate($visitorsCount, $leadsCount),
            rows: $this->buildRows($rows),
        );
    }

    /**
     * @return list<array{
     *     visitorAttributionSource: ?string,
     *     visitorAttributionMedium: ?string,
     *     visitorAttributionCampaign: ?string,
     *     count: int
     * }>
     */
    private function fetchVisitorCounts(?DateRange $range): array
    {
        $query = VisitModel::query()
            ->joinSub($this->firstVisitSubquery(), 'first_visits', function ($join): void {
                $join
                    ->on('visits.visitor_id', '=', 'first_visits.visitor_id')
                    ->on('visits.started_at', '=', 'first_visits.first_started_at');
            })
            ->selectRaw('visits.first_attribution_source as attribution_source, visits.first_attribution_medium as attribution_medium, visits.first_attribution_campaign as attribution_campaign, COUNT(DISTINCT visits.visitor_id) as aggregate')
            ->groupBy(
                'visits.first_attribution_source',
                'visits.first_attribution_medium',
                'visits.first_attribution_campaign',
            );

        $this->applyDateRange($query, 'first_visits.first_started_at', $range);

        /** @var Collection<int, object> $results */
        $results = $query->get();

        return $this->mapGroupedCounts(
            $results,
            'attribution_source',
            'attribution_medium',
            'attribution_campaign',
        );
    }

    /**
     * @return list<array{
     *     visitorAttributionSource: ?string,
     *     visitorAttributionMedium: ?string,
     *     visitorAttributionCampaign: ?string,
     *     count: int
     * }>
     */
    private function fetchLeadCounts(?DateRange $range): array
    {
        $query = LeadModel::query()
            ->joinSub($this->firstVisitSubquery(), 'first_visits', function ($join): void {
                $join->on('leads.visitor_id', '=', 'first_visits.visitor_id');
            })
            ->selectRaw('leads.visitor_attribution_source as attribution_source, leads.visitor_attribution_medium as attribution_medium, leads.visitor_attribution_campaign as attribution_campaign, COUNT(*) as aggregate')
            ->groupBy(
                'leads.visitor_attribution_source',
                'leads.visitor_attribution_medium',
                'leads.visitor_attribution_campaign',
            );

        $this->applyDateRange($query, 'first_visits.first_started_at', $range);

        /** @var Collection<int, object> $results */
        $results = $query->get();

        return $this->mapGroupedCounts(
            $results,
            'attribution_source',
            'attribution_medium',
            'attribution_campaign',
        );
    }

    private function countVisitors(?DateRange $range): int
    {
        $query = VisitModel::query()
            ->joinSub($this->firstVisitSubquery(), 'first_visits', function ($join): void {
                $join
                    ->on('visits.visitor_id', '=', 'first_visits.visitor_id')
                    ->on('visits.started_at', '=', 'first_visits.first_started_at');
            });

        $this->applyDateRange($query, 'first_visits.first_started_at', $range);

        return (int) $query->distinct('visits.visitor_id')->count('visits.visitor_id');
    }

    /**
     * @return Builder<VisitModel>
     */
    private function firstVisitSubquery(): Builder
    {
        return VisitModel::query()
            ->selectRaw('visitor_id, MIN(started_at) as first_started_at')
            ->groupBy('visitor_id');
    }

    /**
     * @param array<string, array{
     *     visitorAttributionSource: ?string,
     *     visitorAttributionMedium: ?string,
     *     visitorAttributionCampaign: ?string,
     *     visitorsCount: int,
     *     leadsCount: int
     * }> $rows
     * @param list<array{
     *     visitorAttributionSource: ?string,
     *     visitorAttributionMedium: ?string,
     *     visitorAttributionCampaign: ?string,
     *     count: int
     * }> $counts
     */
    private function mergeCounts(array &$rows, array $counts, string $field): void
    {
        foreach ($counts as $item) {
            $key = $this->bucketKey(
                $item['visitorAttributionSource'],
                $item['visitorAttributionMedium'],
                $item['visitorAttributionCampaign'],
            );

            if (! isset($rows[$key])) {
                $rows[$key] = [
                    'visitorAttributionSource' => $item['visitorAttributionSource'],
                    'visitorAttributionMedium' => $item['visitorAttributionMedium'],
                    'visitorAttributionCampaign' => $item['visitorAttributionCampaign'],
                    'visitorsCount' => 0,
                    'leadsCount' => 0,
                ];
            }

            $rows[$key][$field] = $item['count'];
        }
    }

    /**
     * @param array<string, array{
     *     visitorAttributionSource: ?string,
     *     visitorAttributionMedium: ?string,
     *     visitorAttributionCampaign: ?string,
     *     visitorsCount: int,
     *     leadsCount: int
     * }> $rows
     * @return list<VisitorAcquisitionFunnelReportRowView>
     */
    private function buildRows(array $rows): array
    {
        $items = array_map(
            fn (array $row): VisitorAcquisitionFunnelReportRowView => new VisitorAcquisitionFunnelReportRowView(
                visitorAttributionSource: $row['visitorAttributionSource'],
                visitorAttributionMedium: $row['visitorAttributionMedium'],
                visitorAttributionCampaign: $row['visitorAttributionCampaign'],
                visitorsCount: $row['visitorsCount'],
                leadsCount: $row['leadsCount'],
                visitorsToLeadsConversionRate: $this->calculateConversionRate($row['visitorsCount'], $row['leadsCount']),
            ),
            array_values($rows),
        );

        usort($items, function (VisitorAcquisitionFunnelReportRowView $left, VisitorAcquisitionFunnelReportRowView $right): int {
            $byLeads = $right->leadsCount <=> $left->leadsCount;

            if ($byLeads !== 0) {
                return $byLeads;
            }

            $byVisitors = $right->visitorsCount <=> $left->visitorsCount;

            if ($byVisitors !== 0) {
                return $byVisitors;
            }

            $bySource = $this->compareNullableStrings($left->visitorAttributionSource, $right->visitorAttributionSource);

            if ($bySource !== 0) {
                return $bySource;
            }

            $byMedium = $this->compareNullableStrings($left->visitorAttributionMedium, $right->visitorAttributionMedium);

            if ($byMedium !== 0) {
                return $byMedium;
            }

            return $this->compareNullableStrings($left->visitorAttributionCampaign, $right->visitorAttributionCampaign);
        });

        return $items;
    }

    /**
     * @param Collection<int, object> $results
     * @return list<array{
     *     visitorAttributionSource: ?string,
     *     visitorAttributionMedium: ?string,
     *     visitorAttributionCampaign: ?string,
     *     count: int
     * }>
     */
    private function mapGroupedCounts(
        Collection $results,
        string $sourceField,
        string $mediumField,
        string $campaignField,
    ): array {
        return $results
            ->map(fn (object $row): array => [
                'visitorAttributionSource' => $this->nullableString($row->{$sourceField} ?? null),
                'visitorAttributionMedium' => $this->nullableString($row->{$mediumField} ?? null),
                'visitorAttributionCampaign' => $this->nullableString($row->{$campaignField} ?? null),
                'count' => (int) ($row->aggregate ?? 0),
            ])
            ->values()
            ->all();
    }

    private function bucketKey(?string $source, ?string $medium, ?string $campaign): string
    {
        return json_encode([$source, $medium, $campaign], JSON_THROW_ON_ERROR);
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function calculateConversionRate(int $fromCount, int $toCount): float
    {
        if ($fromCount <= 0) {
            return 0.0;
        }

        return round(($toCount / $fromCount) * 100, 2);
    }

    private function compareNullableStrings(?string $left, ?string $right): int
    {
        return ($left ?? '') <=> ($right ?? '');
    }

    private function applyDateRange(Builder $query, string $column, ?DateRange $range): void
    {
        if ($range === null) {
            return;
        }

        if ($range->fromInclusive() !== null) {
            $query->where($column, '>=', $range->fromInclusive());
        }

        if ($range->toExclusive() !== null) {
            $query->where($column, '<', $range->toExclusive());
        }
    }
}
