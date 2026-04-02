<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport;

final readonly class GetVisitAttributionFunnelReportHandler
{
    public function __construct(
        private VisitAttributionFunnelReportReadModel $readModel,
    ) {
    }

    public function __invoke(GetVisitAttributionFunnelReportQuery $query): VisitAttributionFunnelReportView
    {
        return ($this->readModel)($query);
    }
}
