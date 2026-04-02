<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport;

interface VisitAttributionFunnelReportReadModel
{
    public function __invoke(GetVisitAttributionFunnelReportQuery $query): VisitAttributionFunnelReportView;
}
