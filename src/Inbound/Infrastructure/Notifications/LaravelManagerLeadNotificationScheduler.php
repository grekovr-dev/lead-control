<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Notifications;

use App\Jobs\SendManagerLeadCreatedTelegramJob;
use Inbound\Application\Reactions\Lead\ManagerLeadNotificationScheduler;
use Inbound\Domain\Lead\LeadId;

final class LaravelManagerLeadNotificationScheduler implements ManagerLeadNotificationScheduler
{
    public function schedule(LeadId $leadId): void
    {
        SendManagerLeadCreatedTelegramJob::dispatch($leadId->value());
    }
}
