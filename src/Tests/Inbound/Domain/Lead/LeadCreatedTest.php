<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\Lead;

use DateTimeImmutable;
use Inbound\Domain\Lead\Events\LeadCreated;
use Inbound\Domain\Lead\LeadId;
use PHPUnit\Framework\TestCase;

final class LeadCreatedTest extends TestCase
{
    public function test_it_exposes_its_data(): void
    {
        $leadId = new LeadId('lead-123');
        $occurredAt = new DateTimeImmutable('2026-04-08T12:00:00+03:00');

        $event = new LeadCreated($leadId, $occurredAt);

        $this->assertSame($leadId, $event->leadId);
        $this->assertSame($occurredAt, $event->occurredAt);
    }
}
