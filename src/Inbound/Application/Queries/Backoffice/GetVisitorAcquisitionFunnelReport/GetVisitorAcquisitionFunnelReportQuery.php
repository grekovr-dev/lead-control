<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport;

use Inbound\Domain\Shared\DateRange;

/**
 * The period here means the period of the visitor's first visit.
 *
 * Leads in the report are counted for visitors from that cohort even if the lead itself
 * was created later.
 */
final readonly class GetVisitorAcquisitionFunnelReportQuery
{
    public function __construct(
        public ?DateRange $firstVisitPeriod = null,
    ) {
    }
}
