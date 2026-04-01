<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\Click;

use DateTimeImmutable;
use Inbound\Domain\Click\Click;
use Inbound\Domain\Click\ClickId;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\VisitId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ClickTest extends TestCase
{
    public function test_it_exposes_its_data_and_normalizes_strings(): void
    {
        $id = new ClickId('click-123');
        $visitorId = new VisitorId('visitor-456');
        $visitId = new VisitId('visit-789');
        $attribution = new Attribution(' google ', ' cpc ', null, null, null, null, null, null, ' https://google.com/ ');
        $occurredAt = new DateTimeImmutable('2026-03-19T10:00:00+02:00');

        $click = new Click(
            $id,
            $visitorId,
            $attribution,
            ' https://example.com/landing ',
            $occurredAt,
            $visitId,
        );

        $this->assertSame($id, $click->id());
        $this->assertSame($visitorId, $click->visitorId());
        $this->assertSame($visitId, $click->visitId());
        $this->assertSame('google', $click->attribution()->source());
        $this->assertSame('cpc', $click->attribution()->medium());
        $this->assertSame('https://example.com/landing', $click->landingUrl());
        $this->assertSame('https://google.com/', $click->attribution()->referrer());
        $this->assertSame($occurredAt, $click->occurredAt());
    }

    public function test_it_normalizes_empty_referrer_to_null(): void
    {
        $click = new Click(
            new ClickId('click-123'),
            new VisitorId('visitor-456'),
            Attribution::empty(),
            'https://example.com/landing',
            new DateTimeImmutable('2026-03-19T10:00:00+02:00'),
        );

        $this->assertNull($click->attribution()->referrer());
    }

    public function test_it_allows_missing_referrer(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-19T10:00:00+02:00');

        $click = new Click(
            new ClickId('click-123'),
            new VisitorId('visitor-456'),
            Attribution::empty(),
            'https://example.com/landing',
            $occurredAt,
        );

        $this->assertSame('https://example.com/landing', $click->landingUrl());
        $this->assertSame($occurredAt, $click->occurredAt());
    }

    public function test_it_rejects_an_empty_landing_url(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Click(
            new ClickId('click-123'),
            new VisitorId('visitor-456'),
            Attribution::empty(),
            '',
            new DateTimeImmutable('2026-03-19T10:00:00+02:00'),
        );
    }

    public function test_it_rejects_a_blank_landing_url(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new Click(
            new ClickId('click-123'),
            new VisitorId('visitor-456'),
            Attribution::empty(),
            '   ',
            new DateTimeImmutable('2026-03-19T10:00:00+02:00'),
        );
    }
}
