<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetOriginFunnelReport;

interface OriginFunnelReportReadModel
{
    public function __invoke(GetOriginFunnelReportQuery $query): OriginFunnelReportView;
}
