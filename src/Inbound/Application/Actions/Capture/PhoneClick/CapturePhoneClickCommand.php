<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\PhoneClick;

use DateTimeImmutable;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Touch\TouchId;

final readonly class CapturePhoneClickCommand
{
    public function __construct(
        public LeadId $leadId,
        public TouchId $touchId,
        public VisitorId $visitorId,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
