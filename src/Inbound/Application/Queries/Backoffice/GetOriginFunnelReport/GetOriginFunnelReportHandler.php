<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetOriginFunnelReport;

final readonly class GetOriginFunnelReportHandler
{
    public function __construct(
        private OriginFunnelReportReadModel $readModel,
    ) {
    }

    public function __invoke(GetOriginFunnelReportQuery $query): OriginFunnelReportView
    {
        return ($this->readModel)($query);
    }
}
