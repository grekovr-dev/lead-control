<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetAttributionFunnelReport;

interface AttributionFunnelReportReadModel
{
    public function __invoke(GetAttributionFunnelReportQuery $query): AttributionFunnelReportView;
}
