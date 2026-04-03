<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport;

final readonly class VisitorAcquisitionFunnelReportRowView
{
    public function __construct(
        public ?string $visitorAttributionSource,
        public ?string $visitorAttributionMedium,
        public ?string $visitorAttributionCampaign,
        public int $visitorsCount,
        public int $leadsCount,
        public float $visitorsToLeadsConversionRate,
    ) {
    }
}
