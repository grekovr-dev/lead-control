<?php

declare(strict_types=1);

namespace Tests\Unit\Inbound\Infrastructure\Notifications;

use App\Jobs\SendManagerLeadCreatedTelegramJob;
use Illuminate\Support\Facades\Queue;
use Inbound\Domain\Lead\LeadId;
use Inbound\Infrastructure\Notifications\LaravelManagerLeadNotificationScheduler;
use Tests\TestCase;

final class LaravelManagerLeadNotificationSchedulerTest extends TestCase
{
    public function test_it_dispatches_manager_lead_notification_job_to_queue(): void
    {
        Queue::fake();

        $scheduler = new LaravelManagerLeadNotificationScheduler;

        $scheduler->schedule(new LeadId('lead-123'));

        Queue::assertPushed(SendManagerLeadCreatedTelegramJob::class, function (SendManagerLeadCreatedTelegramJob $job): bool {
            return $job->leadId === 'lead-123';
        });
    }
}
