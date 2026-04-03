<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetDashboardOverview;

/**
 * @phpstan-type DashboardBreakdownList list<DashboardBreakdownItemView>
 * @phpstan-type DashboardRecentLeadList list<DashboardRecentLeadView>
 */
final readonly class DashboardOverviewView
{
    /**
     * @param DashboardBreakdownList $leadStatusBreakdown
     * @param DashboardBreakdownList $touchTypeBreakdown
     * @param DashboardBreakdownList $leadOriginBreakdown
     * @param DashboardRecentLeadList $recentLeads
     */
    public function __construct(
        public int $clicksCount,
        public int $visitsCount,
        public int $touchesCount,
        public int $leadsCount,
        public float $clicksToLeadsConversionRate,
        public float $visitsToLeadsConversionRate,
        public array $leadStatusBreakdown,
        public array $touchTypeBreakdown,
        public array $leadOriginBreakdown,
        public array $recentLeads,
    ) {
    }
}
