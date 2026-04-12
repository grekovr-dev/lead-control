<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\PhoneClick;

use DateTimeImmutable;
use Inbound\Domain\Shared\VisitorId;

final readonly class CapturePhoneClickCommand
{
    public function __construct(
        public VisitorId $visitorId,
        public DateTimeImmutable $occurredAt,
    ) {}
}
