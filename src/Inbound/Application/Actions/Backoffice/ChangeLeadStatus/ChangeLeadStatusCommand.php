<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Backoffice\ChangeLeadStatus;

use DateTimeImmutable;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Lead\LeadStatus;

final readonly class ChangeLeadStatusCommand
{
    public function __construct(
        public LeadId $leadId,
        public LeadStatus $status,
        public string $ruleKey,
        public DateTimeImmutable $changedAt,
    ) {
    }
}
