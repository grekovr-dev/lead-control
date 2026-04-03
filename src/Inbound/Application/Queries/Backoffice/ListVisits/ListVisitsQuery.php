<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListVisits;

use Inbound\Domain\Shared\DateRange;

final readonly class ListVisitsQuery
{
    public function __construct(
        public ?string $visitorId = null,
        public ?string $firstAttributionSource = null,
        public bool $firstAttributionSourceMissing = false,
        public ?string $firstAttributionMedium = null,
        public bool $firstAttributionMediumMissing = false,
        public ?string $firstAttributionCampaign = null,
        public bool $firstAttributionCampaignMissing = false,
        public ?string $lastAttributionSource = null,
        public ?string $lastAttributionMedium = null,
        public ?DateRange $startedAtRange = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}
