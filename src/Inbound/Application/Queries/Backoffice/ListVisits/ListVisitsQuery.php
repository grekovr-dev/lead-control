<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListVisits;

final readonly class ListVisitsQuery
{
    public function __construct(
        public ?string $visitorId = null,
        public ?string $firstAttributionSource = null,
        public ?string $firstAttributionMedium = null,
        public ?string $lastAttributionSource = null,
        public ?string $lastAttributionMedium = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}
