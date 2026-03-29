<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadStatusReport;

final readonly class LeadStatusReportRowView
{
    public function __construct(
        public string $status,
        public string $statusLabel,
        public int $leadsCount,
        public float $shareOfTotalRate,
    ) {
    }
}
