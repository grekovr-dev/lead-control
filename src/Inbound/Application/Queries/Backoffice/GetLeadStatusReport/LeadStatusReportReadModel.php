<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadStatusReport;

interface LeadStatusReportReadModel
{
    public function __invoke(GetLeadStatusReportQuery $query): LeadStatusReportView;
}
