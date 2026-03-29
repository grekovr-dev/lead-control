<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetAttributionFunnelReport;

final readonly class AttributionFunnelReportRowView
{
    public function __construct(
        public ?string $source,
        public ?string $medium,
        public ?string $campaign,
        public int $rawClicksCount,
        public int $visitsCount,
        public int $leadsCount,
        public float $visitsToLeadsConversionRate,
    ) {
    }
}
