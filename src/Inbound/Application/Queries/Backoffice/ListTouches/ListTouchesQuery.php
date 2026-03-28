<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListTouches;

use Inbound\Domain\Touch\TouchType;

final readonly class ListTouchesQuery
{
    public function __construct(
        public ?string $visitId = null,
        public ?string $visitorId = null,
        public ?TouchType $type = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}
