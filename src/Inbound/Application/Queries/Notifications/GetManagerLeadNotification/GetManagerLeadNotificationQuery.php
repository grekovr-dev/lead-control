<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Notifications\GetManagerLeadNotification;

use Inbound\Domain\Lead\LeadId;

final readonly class GetManagerLeadNotificationQuery
{
    public function __construct(
        public LeadId $leadId,
    ) {}
}
