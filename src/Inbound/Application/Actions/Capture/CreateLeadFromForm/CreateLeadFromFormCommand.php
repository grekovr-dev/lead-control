<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\CreateLeadFromForm;

use DateTimeImmutable;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;

final readonly class CreateLeadFromFormCommand
{
    public function __construct(
        public LeadId $leadId,
        public VisitorId $visitorId,
        public ?string $name,
        public string $phone,
        public Attribution $attribution,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
