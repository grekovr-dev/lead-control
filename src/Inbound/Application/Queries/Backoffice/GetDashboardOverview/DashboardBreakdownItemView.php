<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetDashboardOverview;

final readonly class DashboardBreakdownItemView
{
    public function __construct(
        public string $key,
        public string $label,
        public int $count,
    ) {
    }
}
