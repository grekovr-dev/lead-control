<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadDetails;

use DateTimeImmutable;

final readonly class LeadCoreView
{
    public function __construct(
        public string $leadId,
        public ?string $visitorId,
        public ?string $visitId,
        public ?string $name,
        public ?string $phone,
        public string $status,
        public string $statusLabel,
        public string $origin,
        public string $originLabel,
        public DateTimeImmutable $createdAt,
        public AttributionSnapshotView $attribution,
    ) {
    }
}
