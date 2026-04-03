<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport;

final readonly class VisitAttributionFunnelReportRowView
{
    public function __construct(
        public ?string $source,
        public ?string $medium,
        public ?string $campaign,
        public int $rawClicksCount,
        public int $visitsCount,
        public int $leadsCount,
        public float $rawClicksPerVisitRate,
        public float $visitsToLeadsConversionRate,
    ) {
    }
}
