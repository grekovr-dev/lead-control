<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport;

/**
 * @phpstan-type VisitAttributionFunnelReportRowList list<VisitAttributionFunnelReportRowView>
 */
final readonly class VisitAttributionFunnelReportView
{
    /**
     * @param VisitAttributionFunnelReportRowList $rows
     */
    public function __construct(
        public int $rawClicksCount,
        public int $visitsCount,
        public int $leadsCount,
        public float $rawClicksPerVisitRate,
        public float $visitsToLeadsConversionRate,
        public array $rows,
    ) {
    }
}
