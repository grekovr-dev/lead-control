<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Notifications;

use App\Jobs\Inbound\Notifications\SendManagerLeadCreatedTelegramJob;
use Inbound\Application\Reactions\Lead\ManagerLeadNotificationScheduler;
use Inbound\Domain\Lead\LeadId;

final class LaravelManagerLeadNotificationScheduler implements ManagerLeadNotificationScheduler
{
    public function schedule(LeadId $leadId): void
    {
        if (! (bool) config('services.telegram.notifications_enabled', true)) {
            return;
        }

        SendManagerLeadCreatedTelegramJob::dispatch($leadId->value());
    }
}
