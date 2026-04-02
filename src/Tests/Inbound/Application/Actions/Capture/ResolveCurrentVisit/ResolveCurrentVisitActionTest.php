<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Actions\Capture\ResolveCurrentVisit;

use DateInterval;
use DateTimeImmutable;
use Inbound\Application\Actions\Capture\ResolveCurrentVisit\ResolveCurrentVisitAction;
use Inbound\Application\Actions\Capture\ResolveCurrentVisit\ResolveCurrentVisitCommand;
use Inbound\Application\Actions\Capture\ResolveCurrentVisit\VisitSessionRule;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\Visit;
use Inbound\Domain\Visit\VisitId;
use Inbound\Domain\Visit\VisitRepository;
use PHPUnit\Framework\TestCase;

final class ResolveCurrentVisitActionTest extends TestCase
{
    public function test_it_continues_last_visit_when_session_continues(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-20T14:10:00+02:00');
        $command = new ResolveCurrentVisitCommand(
            new VisitId('visit-new'),
            new VisitorId('visitor-456'),
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            $occurredAt,
        );

        $existingVisit = new Visit(
            new VisitId('visit-existing'),
            $command->visitorId,
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            new Attribution('google', 'remarketing', null, null, null, null, null, null),
            new DateTimeImmutable('2026-03-20T14:00:00+02:00'),
            new DateTimeImmutable('2026-03-20T14:05:00+02:00'),
        );

        $visitRepository = $this->createMock(VisitRepository::class);

        $visitRepository
            ->expects($this->once())
            ->method('findLastByVisitorId')
            ->with($command->visitorId)
            ->willReturn($existingVisit);

        $visitRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Visit $visit) use ($existingVisit, $command, $occurredAt): bool {
                return $visit === $existingVisit
                    && $visit->lastAttribution()->equals($command->attribution)
                    && $visit->lastTouchedAt() == $occurredAt;
            }));

        $action = new ResolveCurrentVisitAction(
            $visitRepository,
            new VisitSessionRule(new DateInterval('PT30M')),
        );

        $result = $action($command);

        $this->assertSame($existingVisit, $result);
    }

    public function test_it_creates_new_visit_when_last_visit_is_missing(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-20T14:10:00+02:00');
        $command = new ResolveCurrentVisitCommand(
            new VisitId('visit-789'),
            new VisitorId('visitor-456'),
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            $occurredAt,
            'https://example.com/landing',
        );

        $visitRepository = $this->createMock(VisitRepository::class);

        $visitRepository
            ->expects($this->once())
            ->method('findLastByVisitorId')
            ->with($command->visitorId)
            ->willReturn(null);

        $visitRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Visit $visit) use ($command, $occurredAt): bool {
                return $visit->id()->equals($command->visitId)
                    && $visit->visitorId()->equals($command->visitorId)
                    && $visit->firstAttribution()->equals($command->attribution)
                    && $visit->lastAttribution()->equals($command->attribution)
                    && $visit->landingUrl() === 'https://example.com/landing'
                    && $visit->startedAt() == $occurredAt
                    && $visit->lastTouchedAt() == $occurredAt;
            }));

        $action = new ResolveCurrentVisitAction(
            $visitRepository,
            new VisitSessionRule(new DateInterval('PT30M')),
        );

        $result = $action($command);

        $this->assertInstanceOf(Visit::class, $result);
        $this->assertTrue($result->id()->equals($command->visitId));
        $this->assertTrue($result->visitorId()->equals($command->visitorId));
    }

    public function test_it_creates_new_visit_when_last_visit_session_is_expired(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-20T14:40:01+02:00');
        $command = new ResolveCurrentVisitCommand(
            new VisitId('visit-789'),
            new VisitorId('visitor-456'),
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            $occurredAt,
            'https://example.com/landing',
        );

        $existingVisit = new Visit(
            new VisitId('visit-existing'),
            $command->visitorId,
            Attribution::empty(),
            Attribution::empty(),
            new DateTimeImmutable('2026-03-20T14:00:00+02:00'),
            new DateTimeImmutable('2026-03-20T14:10:00+02:00'),
        );

        $visitRepository = $this->createMock(VisitRepository::class);

        $visitRepository
            ->expects($this->once())
            ->method('findLastByVisitorId')
            ->with($command->visitorId)
            ->willReturn($existingVisit);

        $visitRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Visit $visit) use ($existingVisit, $command, $occurredAt): bool {
                return $visit !== $existingVisit
                    && $visit->id()->equals($command->visitId)
                    && $visit->visitorId()->equals($command->visitorId)
                    && $visit->firstAttribution()->equals($command->attribution)
                    && $visit->lastAttribution()->equals($command->attribution)
                    && $visit->landingUrl() === 'https://example.com/landing'
                    && $visit->startedAt() == $occurredAt
                    && $visit->lastTouchedAt() == $occurredAt;
            }));

        $action = new ResolveCurrentVisitAction(
            $visitRepository,
            new VisitSessionRule(new DateInterval('PT30M')),
        );

        $result = $action($command);

        $this->assertInstanceOf(Visit::class, $result);
        $this->assertTrue($result->id()->equals($command->visitId));
    }
}
