<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadDetails;

use DateTimeImmutable;

final readonly class LeadVisitSummaryView
{
    public function __construct(
        public string $visitId,
        public string $visitorId,
        public DateTimeImmutable $startedAt,
        public DateTimeImmutable $lastTouchedAt,
        public AttributionSnapshotView $firstAttribution,
        public AttributionSnapshotView $lastAttribution,
    ) {
    }
}
