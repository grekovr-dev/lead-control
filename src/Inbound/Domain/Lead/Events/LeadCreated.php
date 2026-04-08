<?php

declare(strict_types=1);

namespace Inbound\Domain\Lead\Events;

use DateTimeImmutable;
use Inbound\Domain\Lead\LeadId;

final readonly class LeadCreated
{
    public function __construct(
        public LeadId $leadId,
        public DateTimeImmutable $occurredAt,
    ) {}
}
