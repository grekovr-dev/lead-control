<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\RegisterTouch;

use DateTimeImmutable;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Touch\TouchType;

final readonly class RegisterTouchCommand
{
    public function __construct(
        public VisitorId $visitorId,
        public TouchType $type,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
