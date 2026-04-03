<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetFunnelTrends;

final readonly class GetFunnelTrendsHandler
{
    public function __construct(
        private FunnelTrendsReadModel $readModel,
    ) {
    }

    public function __invoke(GetFunnelTrendsQuery $query): FunnelTrendsView
    {
        return ($this->readModel)($query);
    }
}
