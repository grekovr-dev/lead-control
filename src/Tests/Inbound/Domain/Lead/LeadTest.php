<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\Lead;

use DateTimeImmutable;
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
        $attribution = new Attribution(' google ', ' cpc ', null, null, null, null, null, null);
        $createdAt = new DateTimeImmutable('2026-03-19T12:00:00+02:00');

        $lead = new Lead(
            $id,
            $visitorId,
            $visitId,
            ' John Doe ',
            ' +380501112233 ',
            $attribution,
            LeadStatus::NEW,
            ' form ',
            $createdAt,
        );

        $this->assertSame($id, $lead->id());
        $this->assertSame($visitorId, $lead->visitorId());
        $this->assertSame($visitId, $lead->visitId());
        $this->assertSame('John Doe', $lead->name());
        $this->assertSame('+380501112233', $lead->phone());
        $this->assertSame($attribution, $lead->attribution());
        $this->assertSame(LeadStatus::NEW, $lead->status());
        $this->assertSame('form', $lead->origin());
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
        );

        $this->assertNull($lead->name());
        $this->assertNull($lead->phone());
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
        );
    }
}
