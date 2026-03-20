<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\CreateLeadFromPhoneClick;

use DateTimeImmutable;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;

final readonly class CreateLeadFromPhoneClickCommand
{
    public function __construct(
        public LeadId $leadId,
        public VisitorId $visitorId,
        public Attribution $attribution,
        public DateTimeImmutable $occurredAt,
        public ?string $phone = null,
    ) {
    }
}
