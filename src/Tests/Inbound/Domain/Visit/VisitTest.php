<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\Visit;

use DateTimeImmutable;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\Visit;
use Inbound\Domain\Visit\VisitId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class VisitTest extends TestCase
{
    public function test_it_exposes_its_data(): void
    {
        $id = new VisitId('visit-123');
        $visitorId = new VisitorId('visitor-456');
        $firstAttribution = new Attribution('google', 'cpc', null, null, null, null, null, null);
        $lastAttribution = new Attribution('google', 'remarketing', null, null, null, null, null, null);
        $startedAt = new DateTimeImmutable('2026-03-19T10:00:00+02:00');
        $lastTouchedAt = new DateTimeImmutable('2026-03-19T10:05:00+02:00');
        $landingUrl = 'https://example.com/';

        $visit = new Visit(
            $id,
            $visitorId,
            $firstAttribution,
            $lastAttribution,
            $startedAt,
            $lastTouchedAt,
            $landingUrl,
        );

        $this->assertSame($id, $visit->id());
        $this->assertSame($visitorId, $visit->visitorId());
        $this->assertSame($firstAttribution, $visit->firstAttribution());
        $this->assertSame($lastAttribution, $visit->lastAttribution());
        $this->assertSame($landingUrl, $visit->landingUrl());
        $this->assertSame($startedAt, $visit->startedAt());
        $this->assertSame($lastTouchedAt, $visit->lastTouchedAt());
    }

    public function test_it_rejects_started_at_later_than_last_touched_at(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Visit(
            new VisitId('visit-123'),
            new VisitorId('visitor-456'),
            Attribution::empty(),
            Attribution::empty(),
            new DateTimeImmutable('2026-03-19T10:05:00+02:00'),
            new DateTimeImmutable('2026-03-19T10:00:00+02:00'),
        );
    }

    public function test_touch_updates_only_last_touched_at(): void
    {
        $firstAttribution = new Attribution('google', 'cpc', null, null, null, null, null, null);
        $lastAttribution = new Attribution('google', 'remarketing', null, null, null, null, null, null);

        $visit = new Visit(
            new VisitId('visit-123'),
            new VisitorId('visitor-456'),
            $firstAttribution,
            $lastAttribution,
            new DateTimeImmutable('2026-03-19T10:00:00+02:00'),
            new DateTimeImmutable('2026-03-19T10:05:00+02:00'),
        );

        $occurredAt = new DateTimeImmutable('2026-03-19T10:10:00+02:00');

        $visit->touch($occurredAt);

        $this->assertSame($firstAttribution, $visit->firstAttribution());
        $this->assertSame($lastAttribution, $visit->lastAttribution());
        $this->assertSame($occurredAt, $visit->lastTouchedAt());
    }

    public function test_touch_with_attribution_updates_last_attribution_and_last_touched_at(): void
    {
        $firstAttribution = new Attribution('google', 'cpc', null, null, null, null, null, null);
        $lastAttribution = new Attribution('google', 'remarketing', null, null, null, null, null, null);
        $newAttribution = new Attribution('facebook', 'paid-social', null, null, null, null, null, null);

        $visit = new Visit(
            new VisitId('visit-123'),
            new VisitorId('visitor-456'),
            $firstAttribution,
            $lastAttribution,
            new DateTimeImmutable('2026-03-19T10:00:00+02:00'),
            new DateTimeImmutable('2026-03-19T10:05:00+02:00'),
        );

        $occurredAt = new DateTimeImmutable('2026-03-19T10:10:00+02:00');

        $visit->touchWithAttribution($newAttribution, $occurredAt);

        $this->assertSame($firstAttribution, $visit->firstAttribution());
        $this->assertSame($newAttribution, $visit->lastAttribution());
        $this->assertSame($occurredAt, $visit->lastTouchedAt());
    }

    public function test_touch_rejects_date_earlier_than_started_at(): void
    {
        $visit = new Visit(
            new VisitId('visit-123'),
            new VisitorId('visitor-456'),
            Attribution::empty(),
            Attribution::empty(),
            new DateTimeImmutable('2026-03-19T10:00:00+02:00'),
            new DateTimeImmutable('2026-03-19T10:05:00+02:00'),
        );

        $this->expectException(InvalidArgumentException::class);

        $visit->touch(new DateTimeImmutable('2026-03-19T09:59:59+02:00'));
    }

    public function test_touch_rejects_date_earlier_than_current_last_touched_at(): void
    {
        $visit = new Visit(
            new VisitId('visit-123'),
            new VisitorId('visitor-456'),
            Attribution::empty(),
            Attribution::empty(),
            new DateTimeImmutable('2026-03-19T10:00:00+02:00'),
            new DateTimeImmutable('2026-03-19T10:05:00+02:00'),
        );

        $this->expectException(InvalidArgumentException::class);

        $visit->touch(new DateTimeImmutable('2026-03-19T10:04:59+02:00'));
    }
}
