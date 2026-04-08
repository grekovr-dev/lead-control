<?php

declare(strict_types=1);

namespace Tests\Unit\App\Jobs\Inbound\Notifications;

use App\Jobs\Inbound\Notifications\SendManagerLeadCreatedTelegramJob;
use Tests\TestCase;

final class SendManagerLeadCreatedTelegramJobTest extends TestCase
{
    public function test_it_exposes_the_lead_id(): void
    {
        $job = new SendManagerLeadCreatedTelegramJob('lead-123');

        $this->assertSame('lead-123', $job->leadId);
    }
}
