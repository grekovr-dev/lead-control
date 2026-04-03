<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetFunnelTrends;

use DateTimeImmutable;

final readonly class GetFunnelTrendsQuery
{
    public function __construct(
        public ?DateTimeImmutable $dateFrom = null,
        public ?DateTimeImmutable $dateTo = null,
    ) {
    }
}
