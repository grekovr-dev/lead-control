<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListTouches;

use DateTimeImmutable;

final readonly class TouchListItemView
{
    public function __construct(
        public string $touchId,
        public string $visitId,
        public string $visitorId,
        public string $type,
        public string $typeLabel,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
