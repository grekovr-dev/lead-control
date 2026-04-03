<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadStatusReport;

final readonly class GetLeadStatusReportHandler
{
    public function __construct(
        private LeadStatusReportReadModel $readModel,
    ) {
    }

    public function __invoke(GetLeadStatusReportQuery $query): LeadStatusReportView
    {
        return ($this->readModel)($query);
    }
}
