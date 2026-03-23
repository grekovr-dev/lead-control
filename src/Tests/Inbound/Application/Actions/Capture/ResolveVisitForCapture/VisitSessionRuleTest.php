<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Actions\Capture\ResolveVisitForCapture;

use DateInterval;
use DateTimeImmutable;
use Inbound\Application\Actions\Capture\ResolveVisitForCapture\VisitSessionRule;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\Visit;
use Inbound\Domain\Visit\VisitId;
use PHPUnit\Framework\TestCase;

final class VisitSessionRuleTest extends TestCase
{
    public function test_it_continues_visit_within_session_lifetime(): void
    {
        $visit = new Visit(
            new VisitId('visit-123'),
            new VisitorId('visitor-456'),
            Attribution::empty(),
            Attribution::empty(),
            new DateTimeImmutable('2026-03-20T10:00:00+02:00'),
            new DateTimeImmutable('2026-03-20T10:05:00+02:00'),
        );

        $rule = new VisitSessionRule(new DateInterval('PT30M'));

        $this->assertTrue($rule->continues(
            $visit,
            new DateTimeImmutable('2026-03-20T10:35:00+02:00'),
        ));
    }

    public function test_it_does_not_continue_visit_after_session_lifetime(): void
    {
        $visit = new Visit(
            new VisitId('visit-123'),
            new VisitorId('visitor-456'),
            Attribution::empty(),
            Attribution::empty(),
            new DateTimeImmutable('2026-03-20T10:00:00+02:00'),
            new DateTimeImmutable('2026-03-20T10:05:00+02:00'),
        );

        $rule = new VisitSessionRule(new DateInterval('PT30M'));

        $this->assertFalse($rule->continues(
            $visit,
            new DateTimeImmutable('2026-03-20T10:35:01+02:00'),
        ));
    }
}
