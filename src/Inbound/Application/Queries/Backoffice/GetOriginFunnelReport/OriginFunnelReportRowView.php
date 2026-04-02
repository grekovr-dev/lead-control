<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetOriginFunnelReport;

final readonly class OriginFunnelReportRowView
{
    public function __construct(
        public string $origin,
        public string $originLabel,
        public int $touchesCount,
        public int $leadsCount,
        public float $touchesToLeadsConversionRate,
        public ?string $touchDrillType = null,
    ) {}
}
