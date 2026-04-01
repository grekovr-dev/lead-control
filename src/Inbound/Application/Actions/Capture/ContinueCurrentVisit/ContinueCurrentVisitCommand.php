<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\ContinueCurrentVisit;

use DateTimeImmutable;
use Inbound\Domain\Shared\VisitorId;

final readonly class ContinueCurrentVisitCommand
{
    public function __construct(
        public VisitorId $visitorId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
