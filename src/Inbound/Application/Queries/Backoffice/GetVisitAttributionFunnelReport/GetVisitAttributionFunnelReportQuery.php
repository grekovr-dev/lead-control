<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport;

use Inbound\Domain\Shared\DateRange;

final readonly class GetVisitAttributionFunnelReportQuery
{
    public function __construct(
        public ?DateRange $reportPeriod = null,
    ) {
    }
}
