<?php

declare(strict_types=1);

namespace Inbound\Application\Reactions\Lead;

use Inbound\Domain\Lead\LeadId;

interface ManagerLeadNotificationScheduler
{
    public function schedule(LeadId $leadId): void;
}
