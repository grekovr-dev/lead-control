<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetAttributionFunnelReport;

/**
 * @phpstan-type AttributionFunnelReportRowList list<AttributionFunnelReportRowView>
 */
final readonly class AttributionFunnelReportView
{
    /**
     * @param AttributionFunnelReportRowList $rows
     */
    public function __construct(
        public int $rawClicksCount,
        public int $visitsCount,
        public int $leadsCount,
        public float $visitsToLeadsConversionRate,
        public array $rows,
    ) {
    }
}
