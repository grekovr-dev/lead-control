<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport;

interface VisitorAcquisitionFunnelReportReadModel
{
    /**
     * Builds a cohort report for visitors whose first visit falls into the selected period.
     */
    public function __invoke(GetVisitorAcquisitionFunnelReportQuery $query): VisitorAcquisitionFunnelReportView;
}
