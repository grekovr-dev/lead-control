<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetFunnelTrends;

interface FunnelTrendsReadModel
{
    public function __invoke(GetFunnelTrendsQuery $query): FunnelTrendsView;
}
