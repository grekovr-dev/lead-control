<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\GetVisitAttributionFunnelReportQuery;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\VisitAttributionFunnelReportReadModel;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\VisitAttributionFunnelReportRowView;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\VisitAttributionFunnelReportView;
use Inbound\Domain\Shared\DateRange;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;

final class EloquentVisitAttributionFunnelReportReadModel implements VisitAttributionFunnelReportReadModel
{
    public function __invoke(GetVisitAttributionFunnelReportQuery $query): VisitAttributionFunnelReportView
    {
        $clickCounts = $this->fetchClickCounts($query->reportPeriod);
        $rawClicksCount = array_sum(array_column($clickCounts, 'count'));
        $visitsCount = $this->countVisits($query->reportPeriod);
        $leadCounts = $this->fetchLeadCounts($query->reportPeriod);
        $leadsCount = array_sum(array_column($leadCounts, 'count'));

        /** @var array<string, array{
         *     source: ?string,
         *     medium: ?string,
         *     campaign: ?string,
         *     rawClicksCount: int,
         *     visitsCount: int,
         *     leadsCount: int
         * }>
         */
        $rows = [];

        $this->mergeCounts(
            $rows,
            $clickCounts,
            'rawClicksCount',
        );

        $this->mergeCounts(
            $rows,
            $this->fetchVisitCounts($query->reportPeriod),
            'visitsCount',
        );

        $this->mergeCounts(
            $rows,
            $leadCounts,
            'leadsCount',
        );

        return new VisitAttributionFunnelReportView(
            rawClicksCount: $rawClicksCount,
            visitsCount: $visitsCount,
            leadsCount: $leadsCount,
            rawClicksPerVisitRate: $this->calculateRatio($rawClicksCount, $visitsCount),
            visitsToLeadsConversionRate: $this->calculateConversionRate($visitsCount, $leadsCount),
            rows: $this->buildRows($rows),
        );
    }

    /**
     * @return list<array{source: ?string, medium: ?string, campaign: ?string, count: int}>
     */
    private function fetchClickCounts(?DateRange $range): array
    {
        $query = ClickModel::query()
            ->selectRaw('attribution_source, attribution_medium, attribution_campaign, COUNT(*) as aggregate');

        $this->applyDateRange($query, 'occurred_at', $range);

        /** @var Collection<int, object> $results */
        $results = $query
            ->groupBy('attribution_source', 'attribution_medium', 'attribution_campaign')
            ->get();

        return $this->mapGroupedCounts(
            $results,
            'attribution_source',
            'attribution_medium',
            'attribution_campaign',
        );
    }

    /**
     * @return list<array{source: ?string, medium: ?string, campaign: ?string, count: int}>
     */
    private function fetchVisitCounts(?DateRange $range): array
    {
        $query = VisitModel::query()
            ->selectRaw('first_attribution_source, first_attribution_medium, first_attribution_campaign, COUNT(*) as aggregate')
            ->groupBy('first_attribution_source', 'first_attribution_medium', 'first_attribution_campaign');

        $this->applyDateRange($query, 'started_at', $range);

        /** @var Collection<int, object> $results */
        $results = $query
            ->get();

        return $this->mapGroupedCounts(
            $results,
            'first_attribution_source',
            'first_attribution_medium',
            'first_attribution_campaign',
        );
    }

    /**
     * @return list<array{source: ?string, medium: ?string, campaign: ?string, count: int}>
     */
    private function fetchLeadCounts(?DateRange $range): array
    {
        $query = LeadModel::query()
            ->selectRaw('visit_attribution_source as attribution_source, visit_attribution_medium as attribution_medium, visit_attribution_campaign as attribution_campaign, COUNT(*) as aggregate')
            ->groupBy('visit_attribution_source', 'visit_attribution_medium', 'visit_attribution_campaign');

        if ($range !== null) {
            $query->join('visits', 'visits.id', '=', 'leads.visit_id');
            $this->applyDateRange($query, 'visits.started_at', $range);
        }

        /** @var Collection<int, object> $results */
        $results = $query
            ->get();

        return $this->mapGroupedCounts(
            $results,
            'attribution_source',
            'attribution_medium',
            'attribution_campaign',
        );
    }

    private function countVisits(?DateRange $range): int
    {
        $query = VisitModel::query();

        $this->applyDateRange($query, 'started_at', $range);

        return $query->count();
    }

    /**
     * @param array<string, array{
     *     source: ?string,
     *     medium: ?string,
     *     campaign: ?string,
     *     rawClicksCount: int,
     *     visitsCount: int,
     *     leadsCount: int
     * }> $rows
     * @param list<array{source: ?string, medium: ?string, campaign: ?string, count: int}> $counts
     */
    private function mergeCounts(array &$rows, array $counts, string $field): void
    {
        foreach ($counts as $item) {
            $key = $this->bucketKey($item['source'], $item['medium'], $item['campaign']);

            if (!isset($rows[$key])) {
                $rows[$key] = [
                    'source' => $item['source'],
                    'medium' => $item['medium'],
                    'campaign' => $item['campaign'],
                    'rawClicksCount' => 0,
                    'visitsCount' => 0,
                    'leadsCount' => 0,
                ];
            }

            $rows[$key][$field] = $item['count'];
        }
    }

    /**
     * @param array<string, array{
     *     source: ?string,
     *     medium: ?string,
     *     campaign: ?string,
     *     rawClicksCount: int,
     *     visitsCount: int,
     *     leadsCount: int
     * }> $rows
     * @return list<VisitAttributionFunnelReportRowView>
     */
    private function buildRows(array $rows): array
    {
        $items = array_map(
            fn (array $row): VisitAttributionFunnelReportRowView => new VisitAttributionFunnelReportRowView(
                source: $row['source'],
                medium: $row['medium'],
                campaign: $row['campaign'],
                rawClicksCount: $row['rawClicksCount'],
                visitsCount: $row['visitsCount'],
                leadsCount: $row['leadsCount'],
                rawClicksPerVisitRate: $this->calculateRatio($row['rawClicksCount'], $row['visitsCount']),
                visitsToLeadsConversionRate: $this->calculateConversionRate($row['visitsCount'], $row['leadsCount']),
            ),
            array_values($rows),
        );

        usort($items, function (VisitAttributionFunnelReportRowView $left, VisitAttributionFunnelReportRowView $right): int {
            $byLeads = $right->leadsCount <=> $left->leadsCount;

            if ($byLeads !== 0) {
                return $byLeads;
            }

            $byVisits = $right->visitsCount <=> $left->visitsCount;

            if ($byVisits !== 0) {
                return $byVisits;
            }

            $byClicks = $right->rawClicksCount <=> $left->rawClicksCount;

            if ($byClicks !== 0) {
                return $byClicks;
            }

            $bySource = $this->compareNullableStrings($left->source, $right->source);

            if ($bySource !== 0) {
                return $bySource;
            }

            $byMedium = $this->compareNullableStrings($left->medium, $right->medium);

            if ($byMedium !== 0) {
                return $byMedium;
            }

            return $this->compareNullableStrings($left->campaign, $right->campaign);
        });

        return $items;
    }

    /**
     * @param Collection<int, object> $results
     * @return list<array{source: ?string, medium: ?string, campaign: ?string, count: int}>
     */
    private function mapGroupedCounts(
        Collection $results,
        string $sourceField,
        string $mediumField,
        string $campaignField,
    ): array {
        return $results
            ->map(fn (object $row): array => [
                'source' => $this->nullableString($row->{$sourceField} ?? null),
                'medium' => $this->nullableString($row->{$mediumField} ?? null),
                'campaign' => $this->nullableString($row->{$campaignField} ?? null),
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

    private function calculateRatio(int $numerator, int $denominator): float
    {
        if ($denominator <= 0) {
            return 0.0;
        }

        return round($numerator / $denominator, 2);
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
