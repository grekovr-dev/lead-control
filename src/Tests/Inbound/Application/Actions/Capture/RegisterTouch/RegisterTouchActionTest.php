<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Actions\Capture\RegisterTouch;

use DateTimeImmutable;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\ContinueCurrentVisitAction;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\CurrentVisitNotFoundException;
use Inbound\Application\Actions\Capture\RegisterTouch\RegisterTouchAction;
use Inbound\Application\Actions\Capture\RegisterTouch\RegisterTouchCommand;
use Inbound\Application\Transactions\TransactionManager;
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
    public function test_it_uses_existing_visit_when_it_exists(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-20T11:10:00+02:00');
        $command = new RegisterTouchCommand(
            new TouchId('touch-123'),
            new VisitorId('visitor-456'),
            TouchType::LeadFormClick,
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
        $transactionManager = $this->createMock(TransactionManager::class);

        $transactionManager
            ->expects($this->once())
            ->method('run')
            ->with($this->isInstanceOf(\Closure::class))
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $touchRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Touch $touch) use ($command, $existingVisit, $occurredAt): bool {
                return $touch->id()->equals($command->touchId)
                    && $touch->visitId()->equals($existingVisit->id())
                    && $touch->visitorId()->equals($command->visitorId)
                    && $touch->type() === TouchType::LeadFormClick
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
            ->with($this->callback(function (Visit $visit) use ($existingVisit, $occurredAt): bool {
                return $visit === $existingVisit
                    && $visit->lastAttribution()->medium() === 'remarketing'
                    && $visit->lastTouchedAt() == $occurredAt;
            }));

        $action = new RegisterTouchAction(
            $touchRepository,
            new ContinueCurrentVisitAction($visitRepository),
            $transactionManager,
        );

        $result = $action($command);

        $this->assertInstanceOf(Touch::class, $result);
        $this->assertTrue($result->visitId()->equals($existingVisit->id()));
    }

    public function test_it_throws_when_current_visit_is_missing(): void
    {
        $command = new RegisterTouchCommand(
            new TouchId('touch-123'),
            new VisitorId('visitor-456'),
            TouchType::MessengerClick,
            new DateTimeImmutable('2026-03-20T11:10:00+02:00'),
        );

        $touchRepository = $this->createMock(TouchRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);
        $transactionManager = $this->createMock(TransactionManager::class);

        $transactionManager
            ->expects($this->once())
            ->method('run')
            ->with($this->isInstanceOf(\Closure::class))
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $touchRepository
            ->expects($this->never())
            ->method('save');

        $visitRepository
            ->expects($this->once())
            ->method('findLastByVisitorId')
            ->with($command->visitorId)
            ->willReturn(null);

        $visitRepository
            ->expects($this->never())
            ->method('save');

        $action = new RegisterTouchAction(
            $touchRepository,
            new ContinueCurrentVisitAction($visitRepository),
            $transactionManager,
        );

        $this->expectException(CurrentVisitNotFoundException::class);
        $this->expectExceptionMessage('Cannot continue current visit without an existing visit.');

        $action($command);
    }
}
