<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\FunnelTrendPointView;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\FunnelTrendsReadModel;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\FunnelTrendsView;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\GetFunnelTrendsQuery;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;

final class EloquentFunnelTrendsReadModel implements FunnelTrendsReadModel
{
    public function __invoke(GetFunnelTrendsQuery $query): FunnelTrendsView
    {
        /** @var array<string, array{date: string, clicksCount: int, visitsCount: int, leadsCount: int}> $rows */
        $rows = [];

        $this->mergeCounts($rows, $this->fetchClickCounts($query), 'clicksCount');
        $this->mergeCounts($rows, $this->fetchVisitCounts($query), 'visitsCount');
        $this->mergeCounts($rows, $this->fetchLeadCounts($query), 'leadsCount');

        $items = $this->buildRows($rows);

        $clicksCount = array_sum(array_map(
            static fn (FunnelTrendPointView $row): int => $row->clicksCount,
            $items,
        ));
        $visitsCount = array_sum(array_map(
            static fn (FunnelTrendPointView $row): int => $row->visitsCount,
            $items,
        ));
        $leadsCount = array_sum(array_map(
            static fn (FunnelTrendPointView $row): int => $row->leadsCount,
            $items,
        ));

        return new FunnelTrendsView(
            dateFrom: $query->dateFrom,
            dateTo: $query->dateTo,
            clicksCount: $clicksCount,
            visitsCount: $visitsCount,
            leadsCount: $leadsCount,
            clicksToLeadsConversionRate: $this->calculateRate($clicksCount, $leadsCount),
            visitsToLeadsConversionRate: $this->calculateRate($visitsCount, $leadsCount),
            rows: $items,
        );
    }

    /**
     * @return list<array{date: string, count: int}>
     */
    private function fetchClickCounts(GetFunnelTrendsQuery $query): array
    {
        $builder = ClickModel::query()
            ->selectRaw('DATE(occurred_at) as day, COUNT(*) as aggregate')
            ->groupBy('day')
            ->orderBy('day');

        $this->applyDateRange($builder, 'occurred_at', $query);

        /** @var Collection<int, object> $results */
        $results = $builder->get();

        return $this->mapGroupedCounts($results);
    }

    /**
     * @return list<array{date: string, count: int}>
     */
    private function fetchVisitCounts(GetFunnelTrendsQuery $query): array
    {
        $builder = VisitModel::query()
            ->selectRaw('DATE(started_at) as day, COUNT(*) as aggregate')
            ->groupBy('day')
            ->orderBy('day');

        $this->applyDateRange($builder, 'started_at', $query);

        /** @var Collection<int, object> $results */
        $results = $builder->get();

        return $this->mapGroupedCounts($results);
    }

    /**
     * @return list<array{date: string, count: int}>
     */
    private function fetchLeadCounts(GetFunnelTrendsQuery $query): array
    {
        $builder = LeadModel::query()
            ->selectRaw('DATE(created_at) as day, COUNT(*) as aggregate')
            ->groupBy('day')
            ->orderBy('day');

        $this->applyDateRange($builder, 'created_at', $query);

        /** @var Collection<int, object> $results */
        $results = $builder->get();

        return $this->mapGroupedCounts($results);
    }

    private function applyDateRange(Builder $builder, string $field, GetFunnelTrendsQuery $query): void
    {
        if ($query->dateFrom !== null) {
            $builder->where($field, '>=', $query->dateFrom->format('Y-m-d 00:00:00'));
        }

        if ($query->dateTo !== null) {
            $builder->where($field, '<=', $query->dateTo->format('Y-m-d 23:59:59'));
        }
    }

    /**
     * @param Collection<int, object> $results
     * @return list<array{date: string, count: int}>
     */
    private function mapGroupedCounts(Collection $results): array
    {
        return $results
            ->map(fn (object $row): array => [
                'date' => (string) ($row->day ?? ''),
                'count' => (int) ($row->aggregate ?? 0),
            ])
            ->filter(static fn (array $row): bool => $row['date'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param array<string, array{date: string, clicksCount: int, visitsCount: int, leadsCount: int}> $rows
     * @param list<array{date: string, count: int}> $counts
     */
    private function mergeCounts(array &$rows, array $counts, string $field): void
    {
        foreach ($counts as $item) {
            if (!isset($rows[$item['date']])) {
                $rows[$item['date']] = [
                    'date' => $item['date'],
                    'clicksCount' => 0,
                    'visitsCount' => 0,
                    'leadsCount' => 0,
                ];
            }

            $rows[$item['date']][$field] = $item['count'];
        }
    }

    /**
     * @param array<string, array{date: string, clicksCount: int, visitsCount: int, leadsCount: int}> $rows
     * @return list<FunnelTrendPointView>
     */
    private function buildRows(array $rows): array
    {
        $items = array_map(
            fn (array $row): FunnelTrendPointView => new FunnelTrendPointView(
                date: new DateTimeImmutable($row['date'].' 00:00:00'),
                clicksCount: $row['clicksCount'],
                visitsCount: $row['visitsCount'],
                leadsCount: $row['leadsCount'],
                clicksToLeadsConversionRate: $this->calculateRate($row['clicksCount'], $row['leadsCount']),
                visitsToLeadsConversionRate: $this->calculateRate($row['visitsCount'], $row['leadsCount']),
            ),
            array_values($rows),
        );

        usort($items, static fn (FunnelTrendPointView $left, FunnelTrendPointView $right): int => $left->date <=> $right->date);

        return $items;
    }

    private function calculateRate(int $fromCount, int $toCount): float
    {
        if ($fromCount <= 0) {
            return 0.0;
        }

        return round(($toCount / $fromCount) * 100, 2);
    }
}
