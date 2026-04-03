<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetFunnelTrends;

use DateTimeImmutable;

/**
 * @phpstan-type FunnelTrendPointList list<FunnelTrendPointView>
 */
final readonly class FunnelTrendsView
{
    /**
     * @param FunnelTrendPointList $rows
     */
    public function __construct(
        public ?DateTimeImmutable $dateFrom,
        public ?DateTimeImmutable $dateTo,
        public int $clicksCount,
        public int $visitsCount,
        public int $leadsCount,
        public float $clicksToLeadsConversionRate,
        public float $visitsToLeadsConversionRate,
        public array $rows,
    ) {
    }
}
