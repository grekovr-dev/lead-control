<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport;

final readonly class GetVisitorAcquisitionFunnelReportHandler
{
    public function __construct(
        private VisitorAcquisitionFunnelReportReadModel $readModel,
    ) {
    }

    public function __invoke(GetVisitorAcquisitionFunnelReportQuery $query): VisitorAcquisitionFunnelReportView
    {
        return ($this->readModel)($query);
    }
}
