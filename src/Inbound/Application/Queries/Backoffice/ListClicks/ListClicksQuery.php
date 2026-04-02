<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListClicks;

use Inbound\Domain\Shared\DateRange;

final readonly class ListClicksQuery
{
    public function __construct(
        public ?string $visitorId = null,
        public ?string $attributionSource = null,
        public bool $attributionSourceMissing = false,
        public ?string $attributionMedium = null,
        public bool $attributionMediumMissing = false,
        public ?string $attributionCampaign = null,
        public bool $attributionCampaignMissing = false,
        public ?DateRange $occurredAtRange = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}
