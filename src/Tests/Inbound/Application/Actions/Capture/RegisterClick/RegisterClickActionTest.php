<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Actions\Capture\RegisterClick;

use DateTimeImmutable;
use Inbound\Application\Actions\Capture\RegisterClick\RegisterClickAction;
use Inbound\Application\Actions\Capture\RegisterClick\RegisterClickCommand;
use Inbound\Application\Actions\Capture\ResolveCurrentVisit\ResolveCurrentVisitAction;
use Inbound\Application\Actions\Capture\ResolveCurrentVisit\VisitSessionRule;
use Inbound\Application\Transactions\TransactionManager;
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
        $expectedAttribution = (new Attribution('google', 'cpc', null, null, null, null, null, null))
            ->withReferrer(' https://google.com/search?q=ceilings ');
        $command = new RegisterClickCommand(
            new ClickId('click-123'),
            new VisitId('visit-new'),
            new VisitorId('visitor-456'),
            new Attribution('google', 'cpc', null, null, null, null, null, null, ' https://google.com/search?q=ceilings '),
            'https://example.com/landing',
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
        $transactionManager = $this->createMock(TransactionManager::class);

        $transactionManager
            ->expects($this->once())
            ->method('run')
            ->with($this->isInstanceOf(\Closure::class))
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $clickRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Click $click) use ($command, $occurredAt, $existingVisit, $expectedAttribution): bool {
                return $click->id()->equals($command->clickId)
                    && $click->visitorId()->equals($command->visitorId)
                    && $click->visitId()?->equals($existingVisit->id()) === true
                    && $click->attribution()->equals($expectedAttribution)
                    && $click->landingUrl() === 'https://example.com/landing'
                    && $click->attribution()->referrer() === 'https://google.com/search?q=ceilings'
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
            ->with($this->callback(function (Visit $visit) use ($existingVisit, $expectedAttribution, $occurredAt): bool {
                return $visit === $existingVisit
                    && $visit->firstAttribution()->source() === 'google'
                    && $visit->lastAttribution()->equals($expectedAttribution)
                    && $visit->lastTouchedAt() == $occurredAt;
            }));

        $action = new RegisterClickAction(
            $clickRepository,
            new ResolveCurrentVisitAction($visitRepository, new VisitSessionRule),
            $transactionManager,
        );

        $result = $action($command);

        $this->assertSame($existingVisit, $result);
    }

    public function test_it_creates_new_visit_when_last_visit_is_missing(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-20T10:10:00+02:00');
        $expectedAttribution = Attribution::direct();
        $command = new RegisterClickCommand(
            new ClickId('click-123'),
            new VisitId('visit-789'),
            new VisitorId('visitor-456'),
            Attribution::direct(),
            'https://example.com/landing',
            $occurredAt,
        );

        $clickRepository = $this->createMock(ClickRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);
        $transactionManager = $this->createMock(TransactionManager::class);

        $transactionManager
            ->expects($this->once())
            ->method('run')
            ->with($this->isInstanceOf(\Closure::class))
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $clickRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Click $click) use ($command, $expectedAttribution): bool {
                return $click->id()->equals($command->clickId)
                    && $click->visitId()?->equals($command->visitId) === true
                    && $click->attribution()->equals($expectedAttribution);
            }));

        $visitRepository
            ->expects($this->once())
            ->method('findLastByVisitorId')
            ->with($command->visitorId)
            ->willReturn(null);

        $visitRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Visit $visit) use ($command, $occurredAt, $expectedAttribution): bool {
                return $visit->id()->equals($command->visitId)
                    && $visit->visitorId()->equals($command->visitorId)
                    && $visit->firstAttribution()->equals($expectedAttribution)
                    && $visit->lastAttribution()->equals($expectedAttribution)
                    && $visit->landingUrl() === $command->landingUrl
                    && $visit->startedAt() == $occurredAt
                    && $visit->lastTouchedAt() == $occurredAt;
            }));

        $action = new RegisterClickAction(
            $clickRepository,
            new ResolveCurrentVisitAction($visitRepository, new VisitSessionRule),
            $transactionManager,
        );

        $result = $action($command);

        $this->assertInstanceOf(Visit::class, $result);
        $this->assertTrue($result->id()->equals($command->visitId));
        $this->assertTrue($result->visitorId()->equals($command->visitorId));
    }
}
