<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\RegisterTouch;

use DateTimeImmutable;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Touch\TouchId;
use Inbound\Domain\Touch\TouchType;
use Inbound\Domain\Visit\VisitId;

final readonly class RegisterTouchCommand
{
    public function __construct(
        public TouchId $touchId,
        public VisitId $visitId,
        public VisitorId $visitorId,
        public TouchType $type,
        public Attribution $attribution,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
