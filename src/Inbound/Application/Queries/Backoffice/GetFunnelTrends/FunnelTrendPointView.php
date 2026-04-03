<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetFunnelTrends;

use DateTimeImmutable;

final readonly class FunnelTrendPointView
{
    public function __construct(
        public DateTimeImmutable $date,
        public int $clicksCount,
        public int $visitsCount,
        public int $leadsCount,
        public float $clicksToLeadsConversionRate,
        public float $visitsToLeadsConversionRate,
    ) {
    }
}
