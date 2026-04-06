<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadTimeline;

use DateTimeImmutable;

final readonly class LeadTimelineEventView
{
    public function __construct(
        public string $type,
        public DateTimeImmutable $occurredAt,
        public string $title,
        public ?string $description,
        public ?string $origin = null,
        public ?string $originLabel = null,
        public ?string $fromStatus = null,
        public ?string $fromStatusLabel = null,
        public ?string $toStatus = null,
        public ?string $toStatusLabel = null,
        public ?string $ruleKey = null,
        public ?int $authorId = null,
        public ?string $authorLabel = null,
        public ?string $touchType = null,
        public ?string $touchTypeLabel = null,
        public ?string $landingUrl = null,
        public ?string $referrer = null,
    ) {}
}
