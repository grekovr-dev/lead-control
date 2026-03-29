<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadStatusReport;

/**
 * @phpstan-type LeadStatusReportRowList list<LeadStatusReportRowView>
 */
final readonly class LeadStatusReportView
{
    /**
     * @param LeadStatusReportRowList $rows
     */
    public function __construct(
        public int $leadsCount,
        public array $rows,
    ) {
    }
}
