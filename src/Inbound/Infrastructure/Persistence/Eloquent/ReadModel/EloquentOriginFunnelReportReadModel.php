<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use Illuminate\Support\Collection;
use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\GetOriginFunnelReportQuery;
use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\OriginFunnelReportReadModel;
use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\OriginFunnelReportRowView;
use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\OriginFunnelReportView;
use Inbound\Domain\Touch\TouchType;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\TouchModel;

final class EloquentOriginFunnelReportReadModel implements OriginFunnelReportReadModel
{
    public function __invoke(GetOriginFunnelReportQuery $query): OriginFunnelReportView
    {
        unset($query);

        /** @var array<string, array{origin: string, touchesCount: int, leadsCount: int}> $rows */
        $rows = [];

        $this->mergeTouchCounts($rows, $this->fetchTouchCounts());
        $this->mergeLeadCounts($rows, $this->fetchLeadCounts());

        $items = $this->buildRows($rows);
        $touchesCount = array_sum(array_map(
            static fn (OriginFunnelReportRowView $row): int => $row->touchesCount,
            $items,
        ));
        $leadsCount = array_sum(array_map(
            static fn (OriginFunnelReportRowView $row): int => $row->leadsCount,
            $items,
        ));

        return new OriginFunnelReportView(
            touchesCount: $touchesCount,
            leadsCount: $leadsCount,
            touchesToLeadsConversionRate: $this->calculateConversionRate($touchesCount, $leadsCount),
            rows: $items,
        );
    }

    /**
     * @return list<array{origin: string, count: int}>
     */
    private function fetchTouchCounts(): array
    {
        /** @var Collection<int, object> $results */
        $results = TouchModel::query()
            ->selectRaw('type, COUNT(*) as aggregate')
            ->groupBy('type')
            ->get();

        $items = [];

        foreach ($results as $row) {
            $type = $row->type ?? null;
            $touchType = $type instanceof TouchType
                ? $type
                : (is_string($type) ? TouchType::from($type) : null);

            if ($touchType === null) {
                continue;
            }

            $origin = $this->originFromTouchType($touchType);

            if ($origin === null) {
                continue;
            }

            $items[] = [
                'origin' => $origin,
                'count' => (int) ($row->aggregate ?? 0),
            ];
        }

        return $items;
    }

    /**
     * @return list<array{origin: string, count: int}>
     */
    private function fetchLeadCounts(): array
    {
        /** @var Collection<int, object> $results */
        $results = LeadModel::query()
            ->selectRaw('origin, COUNT(*) as aggregate')
            ->groupBy('origin')
            ->get();

        return $results
            ->map(fn (object $row): array => [
                'origin' => (string) ($row->origin ?? ''),
                'count' => (int) ($row->aggregate ?? 0),
            ])
            ->filter(static fn (array $row): bool => $row['origin'] !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<string, array{origin: string, touchesCount: int, leadsCount: int}>  $rows
     * @param  list<array{origin: string, count: int}>  $counts
     */
    private function mergeTouchCounts(array &$rows, array $counts): void
    {
        foreach ($counts as $item) {
            if (! isset($rows[$item['origin']])) {
                $rows[$item['origin']] = [
                    'origin' => $item['origin'],
                    'touchesCount' => 0,
                    'leadsCount' => 0,
                ];
            }

            $rows[$item['origin']]['touchesCount'] = $item['count'];
        }
    }

    /**
     * @param  array<string, array{origin: string, touchesCount: int, leadsCount: int}>  $rows
     * @param  list<array{origin: string, count: int}>  $counts
     */
    private function mergeLeadCounts(array &$rows, array $counts): void
    {
        foreach ($counts as $item) {
            if (! isset($rows[$item['origin']])) {
                $rows[$item['origin']] = [
                    'origin' => $item['origin'],
                    'touchesCount' => 0,
                    'leadsCount' => 0,
                ];
            }

            $rows[$item['origin']]['leadsCount'] = $item['count'];
        }
    }

    /**
     * @param  array<string, array{origin: string, touchesCount: int, leadsCount: int}>  $rows
     * @return list<OriginFunnelReportRowView>
     */
    private function buildRows(array $rows): array
    {
        $items = array_map(
            fn (array $row): OriginFunnelReportRowView => new OriginFunnelReportRowView(
                origin: $row['origin'],
                originLabel: $this->originLabel($row['origin']),
                touchesCount: $row['touchesCount'],
                leadsCount: $row['leadsCount'],
                touchesToLeadsConversionRate: $this->calculateConversionRate($row['touchesCount'], $row['leadsCount']),
                touchDrillType: $this->touchTypeFromOrigin($row['origin'])?->value,
            ),
            array_values($rows),
        );

        usort($items, function (OriginFunnelReportRowView $left, OriginFunnelReportRowView $right): int {
            $byLeads = $right->leadsCount <=> $left->leadsCount;

            if ($byLeads !== 0) {
                return $byLeads;
            }

            $byTouches = $right->touchesCount <=> $left->touchesCount;

            if ($byTouches !== 0) {
                return $byTouches;
            }

            return $left->origin <=> $right->origin;
        });

        return $items;
    }

    private function originFromTouchType(TouchType $type): ?string
    {
        return match ($type) {
            TouchType::LeadFormClick => 'form',
            TouchType::PhoneClick => 'phone_click',
            TouchType::MessengerClick => 'messenger_click',
            TouchType::WorksClick => null,
        };
    }

    private function originLabel(string $origin): string
    {
        return match ($origin) {
            'form' => 'Форма',
            'phone_click' => 'Клік по телефону',
            'messenger_click' => 'Клік по месенджеру',
            default => $origin,
        };
    }

    private function touchTypeFromOrigin(string $origin): ?TouchType
    {
        return match ($origin) {
            'form' => TouchType::LeadFormClick,
            'phone_click' => TouchType::PhoneClick,
            'messenger_click' => TouchType::MessengerClick,
            default => null,
        };
    }

    private function calculateConversionRate(int $fromCount, int $toCount): float
    {
        if ($fromCount <= 0) {
            return 0.0;
        }

        return round(($toCount / $fromCount) * 100, 2);
    }
}
