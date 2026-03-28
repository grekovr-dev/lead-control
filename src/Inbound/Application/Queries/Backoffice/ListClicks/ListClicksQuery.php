<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListClicks;

final readonly class ListClicksQuery
{
    public function __construct(
        public ?string $visitorId = null,
        public ?string $attributionSource = null,
        public ?string $attributionMedium = null,
        public ?string $attributionCampaign = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}
