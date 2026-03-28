<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListClicks;

use DateTimeImmutable;

final readonly class ClickListItemView
{
    public function __construct(
        public string $clickId,
        public string $visitorId,
        public string $landingUrl,
        public ?string $referrer,
        public ?string $attributionSource,
        public ?string $attributionMedium,
        public ?string $attributionCampaign,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
