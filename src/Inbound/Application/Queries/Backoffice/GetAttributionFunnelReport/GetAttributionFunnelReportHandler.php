<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetAttributionFunnelReport;

final readonly class GetAttributionFunnelReportHandler
{
    public function __construct(
        private AttributionFunnelReportReadModel $readModel,
    ) {
    }

    public function __invoke(GetAttributionFunnelReportQuery $query): AttributionFunnelReportView
    {
        return ($this->readModel)($query);
    }
}
