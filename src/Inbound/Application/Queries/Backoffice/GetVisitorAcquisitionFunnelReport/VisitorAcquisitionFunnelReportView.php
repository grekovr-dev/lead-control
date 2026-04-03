<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport;

/**
 * @phpstan-type VisitorAcquisitionFunnelReportRowList list<VisitorAcquisitionFunnelReportRowView>
 */
final readonly class VisitorAcquisitionFunnelReportView
{
    /**
     * @param VisitorAcquisitionFunnelReportRowList $rows
     */
    public function __construct(
        public int $visitorsCount,
        public int $leadsCount,
        public float $visitorsToLeadsConversionRate,
        public array $rows,
    ) {
    }
}
