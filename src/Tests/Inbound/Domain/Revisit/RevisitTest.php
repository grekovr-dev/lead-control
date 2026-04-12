<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\Revisit;

use DateTimeImmutable;
use Inbound\Domain\Revisit\Revisit;
use Inbound\Domain\Revisit\RevisitId;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\VisitId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RevisitTest extends TestCase
{
    public function test_it_exposes_its_data_and_normalizes_strings(): void
    {
        $id = new RevisitId('revisit-123');
        $visitorId = new VisitorId('visitor-456');
        $visitId = new VisitId('visit-789');
        $occurredAt = new DateTimeImmutable('2026-03-19T10:00:00+02:00');

        $revisit = new Revisit(
            $id,
            $visitorId,
            $visitId,
            ' https://example.com/landing ',
            $occurredAt,
        );

        $this->assertSame($id, $revisit->id());
        $this->assertSame($visitorId, $revisit->visitorId());
        $this->assertSame($visitId, $revisit->visitId());
        $this->assertSame('https://example.com/landing', $revisit->landingUrl());
        $this->assertSame($occurredAt, $revisit->occurredAt());
    }

    public function test_it_rejects_an_empty_landing_url(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Revisit(
            new RevisitId('revisit-123'),
            new VisitorId('visitor-456'),
            new VisitId('visit-789'),
            '',
            new DateTimeImmutable('2026-03-19T10:00:00+02:00'),
        );
    }

    public function test_it_rejects_a_blank_landing_url(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Revisit(
            new RevisitId('revisit-123'),
            new VisitorId('visitor-456'),
            new VisitId('visit-789'),
            '   ',
            new DateTimeImmutable('2026-03-19T10:00:00+02:00'),
        );
    }
}
