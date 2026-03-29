<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetOriginFunnelReport;

/**
 * @phpstan-type OriginFunnelReportRowList list<OriginFunnelReportRowView>
 */
final readonly class OriginFunnelReportView
{
    /**
     * @param OriginFunnelReportRowList $rows
     */
    public function __construct(
        public int $touchesCount,
        public int $leadsCount,
        public float $touchesToLeadsConversionRate,
        public array $rows,
    ) {
    }
}
