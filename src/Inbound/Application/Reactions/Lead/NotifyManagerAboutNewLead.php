<?php

declare(strict_types=1);

namespace Inbound\Application\Reactions\Lead;

use Inbound\Domain\Lead\Events\LeadCreated;

final readonly class NotifyManagerAboutNewLead
{
    public function __construct(
        private ManagerLeadNotificationScheduler $scheduler,
    ) {}

    public function __invoke(LeadCreated $event): void
    {
        $this->scheduler->schedule($event->leadId);
    }
}
