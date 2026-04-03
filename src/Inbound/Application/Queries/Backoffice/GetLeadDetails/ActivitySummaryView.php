<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadDetails;

use DateTimeImmutable;

final readonly class ActivitySummaryView
{
    public function __construct(
        public int $count,
        public ?DateTimeImmutable $lastOccurredAt,
    ) {
    }
}
