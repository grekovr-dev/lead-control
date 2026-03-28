<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetDashboardOverview;

interface DashboardOverviewReadModel
{
    public function __invoke(GetDashboardOverviewQuery $query): DashboardOverviewView;
}
