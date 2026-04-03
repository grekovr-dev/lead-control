<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use Illuminate\Database\Eloquent\Builder;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\GetLeadStatusReportQuery;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\LeadStatusReportReadModel;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\LeadStatusReportRowView;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\LeadStatusReportView;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\Shared\DateRange;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;

final class EloquentLeadStatusReportReadModel implements LeadStatusReportReadModel
{
    public function __invoke(GetLeadStatusReportQuery $query): LeadStatusReportView
    {
        $leadQuery = LeadModel::query();

        $this->applyLeadCreatedAtRange($leadQuery, $query->leadCreatedAtRange);

        $leadsCount = (clone $leadQuery)->count();

        /** @var array<string, int|string> $counts */
        $counts = (clone $leadQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->all();

        $rows = [];

        foreach (LeadStatus::cases() as $status) {
            $count = (int) ($counts[$status->value] ?? 0);

            $rows[] = new LeadStatusReportRowView(
                status: $status->value,
                statusLabel: $status->label(),
                leadsCount: $count,
                shareOfTotalRate: $this->calculateRate($leadsCount, $count),
            );
        }

        return new LeadStatusReportView(
            leadsCount: $leadsCount,
            rows: $rows,
        );
    }

    private function calculateRate(int $total, int $count): float
    {
        if ($total <= 0) {
            return 0.0;
        }

        return round(($count / $total) * 100, 2);
    }

    private function applyLeadCreatedAtRange(Builder $query, ?DateRange $range): void
    {
        if ($range === null) {
            return;
        }

        if ($range->fromInclusive() !== null) {
            $query->where('created_at', '>=', $range->fromInclusive());
        }

        if ($range->toExclusive() !== null) {
            $query->where('created_at', '<', $range->toExclusive());
        }
    }
}
