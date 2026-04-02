<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\ResolveCurrentVisit;

use DateTimeImmutable;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\VisitId;

final readonly class ResolveCurrentVisitCommand
{
    public function __construct(
        public VisitId $visitId,
        public VisitorId $visitorId,
        public Attribution $attribution,
        public DateTimeImmutable $occurredAt,
        public ?string $landingUrl = null,
    ) {
    }
}
