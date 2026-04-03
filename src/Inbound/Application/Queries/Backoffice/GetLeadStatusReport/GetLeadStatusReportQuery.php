<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadStatusReport;

use Inbound\Domain\Shared\DateRange;

final readonly class GetLeadStatusReportQuery
{
    public function __construct(
        public ?DateRange $leadCreatedAtRange = null,
    ) {
    }
}
