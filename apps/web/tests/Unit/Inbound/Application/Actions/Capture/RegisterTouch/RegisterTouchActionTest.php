<?php

declare(strict_types=1);

namespace Tests\Unit\Inbound\Application\Actions\Capture\RegisterTouch;

use DateTimeImmutable;
use Inbound\Application\Actions\Capture\RegisterTouch\RegisterTouchAction;
use Inbound\Application\Actions\Capture\RegisterTouch\RegisterTouchCommand;
use Inbound\Application\Actions\Capture\ResolveVisitForCapture\ResolveVisitForCaptureAction;
use Inbound\Application\Actions\Capture\ResolveVisitForCapture\VisitSessionRule;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Touch\Touch;
use Inbound\Domain\Touch\TouchId;
use Inbound\Domain\Touch\TouchRepository;
use Inbound\Domain\Touch\TouchType;
use Inbound\Domain\Visit\Visit;
use Inbound\Domain\Visit\VisitId;
use Inbound\Domain\Visit\VisitRepository;
use PHPUnit\Framework\TestCase;

final class RegisterTouchActionTest extends TestCase
{
    public function test_it_uses_existing_visit_when_session_continues(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-20T11:10:00+02:00');
        $command = new RegisterTouchCommand(
            new TouchId('touch-123'),
            new VisitId('visit-new'),
            new VisitorId('visitor-456'),
            TouchType::FormSubmit,
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            $occurredAt,
        );

        $existingVisit = new Visit(
            new VisitId('visit-existing'),
            $command->visitorId,
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            new Attribution('google', 'remarketing', null, null, null, null, null, null),
            new DateTimeImmutable('2026-03-20T11:00:00+02:00'),
            new DateTimeImmutable('2026-03-20T11:05:00+02:00'),
        );

        $touchRepository = $this->createMock(TouchRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);

        $touchRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Touch $touch) use ($command, $existingVisit, $occurredAt): bool {
                return $touch->id()->equals($command->touchId)
                    && $touch->visitId()->equals($existingVisit->id())
                    && $touch->visitorId()->equals($command->visitorId)
                    && $touch->type() === TouchType::FormSubmit
                    && $touch->occurredAt() == $occurredAt;
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

        $action = new RegisterTouchAction(
            $touchRepository,
            new ResolveVisitForCaptureAction($visitRepository, new VisitSessionRule()),
        );

        $result = $action($command);

        $this->assertInstanceOf(Touch::class, $result);
        $this->assertTrue($result->visitId()->equals($existingVisit->id()));
    }

    public function test_it_creates_new_visit_when_last_visit_is_missing(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-20T11:10:00+02:00');
        $command = new RegisterTouchCommand(
            new TouchId('touch-123'),
            new VisitId('visit-789'),
            new VisitorId('visitor-456'),
            TouchType::MessengerClick,
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            $occurredAt,
        );

        $touchRepository = $this->createMock(TouchRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);

        $touchRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Touch $touch) use ($command, $occurredAt): bool {
                return $touch->id()->equals($command->touchId)
                    && $touch->visitId()->equals($command->visitId)
                    && $touch->visitorId()->equals($command->visitorId)
                    && $touch->type() === TouchType::MessengerClick
                    && $touch->occurredAt() == $occurredAt;
            }));

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

        $action = new RegisterTouchAction(
            $touchRepository,
            new ResolveVisitForCaptureAction($visitRepository, new VisitSessionRule()),
        );

        $result = $action($command);

        $this->assertInstanceOf(Touch::class, $result);
        $this->assertTrue($result->id()->equals($command->touchId));
        $this->assertTrue($result->visitId()->equals($command->visitId));
    }
}
