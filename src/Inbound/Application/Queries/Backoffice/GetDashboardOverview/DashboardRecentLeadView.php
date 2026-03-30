<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetDashboardOverview;

use DateTimeImmutable;

final readonly class DashboardRecentLeadView
{
    public function __construct(
        public string $leadId,
        public string $shortLeadId,
        public ?string $visitorId,
        public ?string $visitId,
        public ?string $name,
        public ?string $phone,
        public string $status,
        public string $statusLabel,
        public string $origin,
        public string $originLabel,
        public ?string $attributionSource,
        public ?string $attributionMedium,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
