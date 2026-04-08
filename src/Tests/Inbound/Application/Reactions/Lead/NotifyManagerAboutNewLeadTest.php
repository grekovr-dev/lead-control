<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Reactions\Lead;

use DateTimeImmutable;
use Inbound\Application\Reactions\Lead\ManagerLeadNotificationScheduler;
use Inbound\Application\Reactions\Lead\NotifyManagerAboutNewLead;
use Inbound\Domain\Lead\Events\LeadCreated;
use Inbound\Domain\Lead\LeadId;
use PHPUnit\Framework\TestCase;

final class NotifyManagerAboutNewLeadTest extends TestCase
{
    public function test_it_schedules_manager_notification_for_created_lead(): void
    {
        $leadId = new LeadId('lead-123');
        $scheduler = $this->createMock(ManagerLeadNotificationScheduler::class);

        $scheduler
            ->expects($this->once())
            ->method('schedule')
            ->with($leadId);

        $reaction = new NotifyManagerAboutNewLead($scheduler);

        $reaction(new LeadCreated(
            $leadId,
            new DateTimeImmutable('2026-04-08T14:00:00+03:00'),
        ));
    }
}
