<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Actions\Capture\RegisterClick;

use DateTimeImmutable;
use Inbound\Application\Actions\Capture\RegisterClick\RegisterClickAction;
use Inbound\Application\Actions\Capture\RegisterClick\RegisterClickCommand;
use Inbound\Application\Actions\Capture\ResolveVisitForCapture\ResolveVisitForCaptureAction;
use Inbound\Application\Actions\Capture\ResolveVisitForCapture\VisitSessionRule;
use Inbound\Domain\Click\Click;
use Inbound\Domain\Click\ClickId;
use Inbound\Domain\Click\ClickRepository;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\Visit;
use Inbound\Domain\Visit\VisitId;
use Inbound\Domain\Visit\VisitRepository;
use PHPUnit\Framework\TestCase;

final class RegisterClickActionTest extends TestCase
{
    public function test_it_uses_existing_visit_when_session_continues(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-20T10:10:00+02:00');
        $command = new RegisterClickCommand(
            new ClickId('click-123'),
            new VisitId('visit-new'),
            new VisitorId('visitor-456'),
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            'https://example.com/landing',
            ' https://google.com/search?q=ceilings ',
            $occurredAt,
        );

        $existingVisit = new Visit(
            new VisitId('visit-existing'),
            $command->visitorId,
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            new Attribution('google', 'remarketing', null, null, null, null, null, null),
            new DateTimeImmutable('2026-03-20T10:00:00+02:00'),
            new DateTimeImmutable('2026-03-20T10:05:00+02:00'),
        );

        $clickRepository = $this->createMock(ClickRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);

        $clickRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Click $click) use ($command, $occurredAt): bool {
                return $click->id()->equals($command->clickId)
                    && $click->visitorId()->equals($command->visitorId)
                    && $click->attribution()->equals($command->attribution)
                    && $click->landingUrl() === 'https://example.com/landing'
                    && $click->referrer() === 'https://google.com/search?q=ceilings'
                    && $click->occurredAt() == $occurredAt;
            }));

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
                    && $visit->firstAttribution()->source() === 'google'
                    && $visit->lastAttribution()->equals($command->attribution)
                    && $visit->lastTouchedAt() == $occurredAt;
            }));

        $action = new RegisterClickAction(
            $clickRepository,
            new ResolveVisitForCaptureAction($visitRepository, new VisitSessionRule()),
        );

        $result = $action($command);

        $this->assertSame($existingVisit, $result);
    }

    public function test_it_creates_new_visit_when_last_visit_is_missing(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-20T10:10:00+02:00');
        $command = new RegisterClickCommand(
            new ClickId('click-123'),
            new VisitId('visit-789'),
            new VisitorId('visitor-456'),
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            'https://example.com/landing',
            null,
            $occurredAt,
        );

        $clickRepository = $this->createMock(ClickRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);

        $clickRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->isInstanceOf(Click::class));

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
                    && $visit->startedAt() == $occurredAt
                    && $visit->lastTouchedAt() == $occurredAt;
            }));

        $action = new RegisterClickAction(
            $clickRepository,
            new ResolveVisitForCaptureAction($visitRepository, new VisitSessionRule()),
        );

        $result = $action($command);

        $this->assertInstanceOf(Visit::class, $result);
        $this->assertTrue($result->id()->equals($command->visitId));
        $this->assertTrue($result->visitorId()->equals($command->visitorId));
    }
}
