<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListVisits;

use DateTimeImmutable;

final readonly class VisitListItemView
{
    public function __construct(
        public string $visitId,
        public string $visitorId,
        public ?string $firstAttributionSource,
        public ?string $firstAttributionMedium,
        public ?string $firstAttributionCampaign,
        public ?string $lastAttributionSource,
        public ?string $lastAttributionMedium,
        public DateTimeImmutable $startedAt,
        public DateTimeImmutable $lastTouchedAt,
    ) {
    }
}
