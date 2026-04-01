<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use Illuminate\Support\Collection;
use Inbound\Application\Queries\Backoffice\GetAttributionFunnelReport\AttributionFunnelReportReadModel;
use Inbound\Application\Queries\Backoffice\GetAttributionFunnelReport\AttributionFunnelReportRowView;
use Inbound\Application\Queries\Backoffice\GetAttributionFunnelReport\AttributionFunnelReportView;
use Inbound\Application\Queries\Backoffice\GetAttributionFunnelReport\GetAttributionFunnelReportQuery;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;

final class EloquentAttributionFunnelReportReadModel implements AttributionFunnelReportReadModel
{
    public function __invoke(GetAttributionFunnelReportQuery $query): AttributionFunnelReportView
    {
        unset($query);

        $rawClicksCount = ClickModel::query()->count();
        $visitsCount = VisitModel::query()->count();
        $leadCounts = $this->fetchLeadCounts();
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
            $this->fetchClickCounts(),
            'rawClicksCount',
        );

        $this->mergeCounts(
            $rows,
            $this->fetchVisitCounts(),
            'visitsCount',
        );

        $this->mergeCounts(
            $rows,
            $leadCounts,
            'leadsCount',
        );

        return new AttributionFunnelReportView(
            rawClicksCount: $rawClicksCount,
            visitsCount: $visitsCount,
            leadsCount: $leadsCount,
            visitsToLeadsConversionRate: $this->calculateConversionRate($visitsCount, $leadsCount),
            rows: $this->buildRows($rows),
        );
    }

    /**
     * @return list<array{source: ?string, medium: ?string, campaign: ?string, count: int}>
     */
    private function fetchClickCounts(): array
    {
        /** @var Collection<int, object> $results */
        $results = ClickModel::query()
            ->selectRaw('attribution_source, attribution_medium, attribution_campaign, COUNT(*) as aggregate')
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
    private function fetchVisitCounts(): array
    {
        /** @var Collection<int, object> $results */
        $results = VisitModel::query()
            ->selectRaw('first_attribution_source, first_attribution_medium, first_attribution_campaign, COUNT(*) as aggregate')
            ->groupBy('first_attribution_source', 'first_attribution_medium', 'first_attribution_campaign')
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
    private function fetchLeadCounts(): array
    {
        /** @var Collection<int, object> $results */
        $results = LeadModel::query()
            ->selectRaw('visit_attribution_source as attribution_source, visit_attribution_medium as attribution_medium, visit_attribution_campaign as attribution_campaign, COUNT(*) as aggregate')
            ->groupBy('visit_attribution_source', 'visit_attribution_medium', 'visit_attribution_campaign')
            ->get();

        return $this->mapGroupedCounts(
            $results,
            'attribution_source',
            'attribution_medium',
            'attribution_campaign',
        );
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
     * @return list<AttributionFunnelReportRowView>
     */
    private function buildRows(array $rows): array
    {
        $items = array_map(
            fn (array $row): AttributionFunnelReportRowView => new AttributionFunnelReportRowView(
                source: $row['source'],
                medium: $row['medium'],
                campaign: $row['campaign'],
                rawClicksCount: $row['rawClicksCount'],
                visitsCount: $row['visitsCount'],
                leadsCount: $row['leadsCount'],
                visitsToLeadsConversionRate: $this->calculateConversionRate($row['visitsCount'], $row['leadsCount']),
            ),
            array_values($rows),
        );

        usort($items, function (AttributionFunnelReportRowView $left, AttributionFunnelReportRowView $right): int {
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

    private function compareNullableStrings(?string $left, ?string $right): int
    {
        return ($left ?? '') <=> ($right ?? '');
    }
}
