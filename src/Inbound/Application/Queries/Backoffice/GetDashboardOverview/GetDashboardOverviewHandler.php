<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetDashboardOverview;

final readonly class GetDashboardOverviewHandler
{
    public function __construct(
        private DashboardOverviewReadModel $readModel,
    ) {
    }

    public function __invoke(GetDashboardOverviewQuery $query): DashboardOverviewView
    {
        return ($this->readModel)($query);
    }
}
