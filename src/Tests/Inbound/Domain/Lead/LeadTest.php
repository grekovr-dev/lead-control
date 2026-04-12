<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\Lead;

use DateTimeImmutable;
use Inbound\Domain\Lead\Events\LeadCreated;
use Inbound\Domain\Lead\Lead;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\VisitId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LeadTest extends TestCase
{
    public function test_it_exposes_its_data_and_normalizes_strings(): void
    {
        $id = new LeadId('lead-123');
        $visitorId = new VisitorId('visitor-456');
        $visitId = new VisitId('visit-789');
        $visitAttribution = new Attribution(' google ', ' cpc ', null, null, null, null, null, null);
        $visitorAttribution = new Attribution(' direct ', null, null, null, null, null, null, null);
        $createdAt = new DateTimeImmutable('2026-03-19T12:00:00+02:00');
        $landingUrl = 'https://example.com/';

        $lead = new Lead(
            $id,
            $visitorId,
            $visitId,
            ' John Doe ',
            ' +380501112233 ',
            $visitAttribution,
            LeadStatus::NEW,
            ' form ',
            $createdAt,
            $visitorAttribution,
            $landingUrl,
        );

        $this->assertSame($id, $lead->id());
        $this->assertSame($visitorId, $lead->visitorId());
        $this->assertSame($visitId, $lead->visitId());
        $this->assertSame('John Doe', $lead->name());
        $this->assertSame('+380501112233', $lead->phone());
        $this->assertSame($visitAttribution, $lead->visitAttribution());
        $this->assertSame($visitorAttribution, $lead->visitorAttribution());
        $this->assertSame(LeadStatus::NEW, $lead->status());
        $this->assertSame('form', $lead->origin());
        $this->assertSame($landingUrl, $lead->landingUrl());
        $this->assertSame($createdAt, $lead->createdAt());
    }

    public function test_it_allows_missing_phone_and_normalizes_blank_strings_to_null(): void
    {
        $lead = new Lead(
            new LeadId('lead-123'),
            new VisitorId('visitor-456'),
            new VisitId('visit-789'),
            '   ',
            null,
            Attribution::empty(),
            LeadStatus::NEW,
            'phone_click',
            new DateTimeImmutable('2026-03-19T12:00:00+02:00'),
            Attribution::empty(),
        );

        $this->assertNull($lead->name());
        $this->assertNull($lead->phone());
        $this->assertTrue($lead->visitorAttribution()->equals($lead->visitAttribution()));
        $this->assertNull($lead->landingUrl());
    }

    public function test_change_status_updates_the_current_status(): void
    {
        $lead = new Lead(
            new LeadId('lead-123'),
            new VisitorId('visitor-456'),
            new VisitId('visit-789'),
            'John Doe',
            '+380501112233',
            Attribution::empty(),
            LeadStatus::NEW,
            'form',
            new DateTimeImmutable('2026-03-19T12:00:00+02:00'),
            Attribution::empty(),
        );

        $lead->changeStatus(LeadStatus::QUALIFIED);

        $this->assertSame(LeadStatus::QUALIFIED, $lead->status());
        $this->assertSame('form', $lead->origin());
    }

    public function test_it_rejects_a_blank_origin(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Lead(
            new LeadId('lead-123'),
            new VisitorId('visitor-456'),
            new VisitId('visit-789'),
            null,
            null,
            Attribution::empty(),
            LeadStatus::NEW,
            '   ',
            new DateTimeImmutable('2026-03-19T12:00:00+02:00'),
            Attribution::empty(),
        );
    }

    public function test_it_rejects_an_invalid_origin(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Lead(
            new LeadId('lead-123'),
            new VisitorId('visitor-456'),
            new VisitId('visit-789'),
            null,
            null,
            Attribution::empty(),
            LeadStatus::NEW,
            'landing',
            new DateTimeImmutable('2026-03-19T12:00:00+02:00'),
            Attribution::empty(),
        );
    }

    public function test_it_records_and_releases_events(): void
    {
        $lead = new Lead(
            new LeadId('lead-123'),
            new VisitorId('visitor-456'),
            new VisitId('visit-789'),
            null,
            null,
            Attribution::empty(),
            LeadStatus::NEW,
            'form',
            new DateTimeImmutable('2026-03-19T12:00:00+02:00'),
            Attribution::empty(),
        );

        $firstEvent = new \stdClass;
        $secondEvent = new \stdClass;

        $lead->recordThat($firstEvent);
        $lead->recordThat($secondEvent);

        $this->assertSame([$firstEvent, $secondEvent], $lead->releaseEvents());
        $this->assertSame([], $lead->releaseEvents());
    }

    public function test_create_records_lead_created_event(): void
    {
        $leadId = new LeadId('lead-123');
        $createdAt = new DateTimeImmutable('2026-03-19T12:00:00+02:00');

        $lead = Lead::create(
            $leadId,
            new VisitorId('visitor-456'),
            new VisitId('visit-789'),
            'John Doe',
            '+380501112233',
            Attribution::empty(),
            LeadStatus::NEW,
            'form',
            $createdAt,
            Attribution::empty(),
            'https://example.com/',
        );

        $events = $lead->releaseEvents();

        $this->assertCount(1, $events);
        $this->assertInstanceOf(LeadCreated::class, $events[0]);
        $this->assertSame($leadId, $events[0]->leadId);
        $this->assertSame($createdAt, $events[0]->occurredAt);
        $this->assertSame([], $lead->releaseEvents());
    }

    public function test_plain_constructor_does_not_record_events(): void
    {
        $lead = new Lead(
            new LeadId('lead-123'),
            new VisitorId('visitor-456'),
            new VisitId('visit-789'),
            null,
            null,
            Attribution::empty(),
            LeadStatus::NEW,
            'form',
            new DateTimeImmutable('2026-03-19T12:00:00+02:00'),
            Attribution::empty(),
        );

        $this->assertSame([], $lead->releaseEvents());
    }
}
