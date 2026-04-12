<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Actions\Capture\RegisterClick;

use DateTimeImmutable;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\ContinueCurrentVisitAction;
use Inbound\Application\Actions\Capture\RegisterClick\RegisterClickAction;
use Inbound\Application\Actions\Capture\RegisterClick\RegisterClickCommand;
use Inbound\Application\Actions\Capture\ResolveCurrentVisit\ResolveCurrentVisitAction;
use Inbound\Application\Actions\Capture\ResolveCurrentVisit\VisitSessionRule;
use Inbound\Application\Identifiers\UuidGenerator;
use Inbound\Application\Transactions\TransactionManager;
use Inbound\Domain\Click\Click;
use Inbound\Domain\Click\ClickId;
use Inbound\Domain\Click\ClickRepository;
use Inbound\Domain\Revisit\Revisit;
use Inbound\Domain\Revisit\RevisitId;
use Inbound\Domain\Revisit\RevisitRepository;
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
        $expectedAttribution = new Attribution(
            'google',
            'cpc',
            null,
            null,
            null,
            null,
            null,
            null,
            ' https://google.com/search?q=ceilings ',
        );
        $command = new RegisterClickCommand(
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
        $revisitRepository = $this->createMock(RevisitRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);
        $clickUuidGenerator = $this->createMock(UuidGenerator::class);
        $visitUuidGenerator = $this->createMock(UuidGenerator::class);
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
                return $click->id()->equals(new ClickId('click-123'))
                    && $click->visitorId()->equals($command->visitorId)
                    && $click->visitId()?->equals($existingVisit->id()) === true
                    && $click->attribution()->equals($expectedAttribution)
                    && $click->landingUrl() === 'https://example.com/landing'
                    && $click->attribution()->referrer() === 'https://google.com/search?q=ceilings'
                    && $click->occurredAt() == $occurredAt;
            }));

        $revisitRepository
            ->expects($this->never())
            ->method('save');

        $clickUuidGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('click-123');

        $visitUuidGenerator
            ->expects($this->never())
            ->method('generate');

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
            $revisitRepository,
            new ContinueCurrentVisitAction($visitRepository),
            new ResolveCurrentVisitAction($visitRepository, new VisitSessionRule, $visitUuidGenerator),
            $clickUuidGenerator,
            $transactionManager,
        );

        $result = $action($command);

        $this->assertSame($existingVisit, $result);
    }

    public function test_it_continues_current_visit_without_creating_click_for_direct_revisit(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-20T10:10:00+02:00');
        $command = new RegisterClickCommand(
            new VisitorId('visitor-456'),
            Attribution::direct(),
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
        $revisitRepository = $this->createMock(RevisitRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);
        $clickUuidGenerator = $this->createMock(UuidGenerator::class);
        $visitUuidGenerator = $this->createMock(UuidGenerator::class);
        $transactionManager = $this->createMock(TransactionManager::class);

        $transactionManager
            ->expects($this->once())
            ->method('run')
            ->with($this->isInstanceOf(\Closure::class))
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $clickRepository
            ->expects($this->never())
            ->method('save');

        $revisitRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Revisit $revisit) use ($command, $occurredAt, $existingVisit): bool {
                return $revisit->id()->equals(new RevisitId('revisit-123'))
                    && $revisit->visitorId()->equals($command->visitorId)
                    && $revisit->visitId()->equals($existingVisit->id())
                    && $revisit->landingUrl() === 'https://example.com/landing'
                    && $revisit->occurredAt() == $occurredAt;
            }));

        $clickUuidGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('revisit-123');

        $visitUuidGenerator
            ->expects($this->never())
            ->method('generate');

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
                    && $visit->lastTouchedAt() == $occurredAt;
            }));

        $action = new RegisterClickAction(
            $clickRepository,
            $revisitRepository,
            new ContinueCurrentVisitAction($visitRepository),
            new ResolveCurrentVisitAction($visitRepository, new VisitSessionRule, $visitUuidGenerator),
            $clickUuidGenerator,
            $transactionManager,
        );

        $result = $action($command);

        $this->assertSame($existingVisit, $result);
    }

    public function test_it_creates_new_visit_when_direct_visit_has_no_current_visit(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-20T10:10:00+02:00');
        $expectedAttribution = Attribution::direct();
        $command = new RegisterClickCommand(
            new VisitorId('visitor-456'),
            Attribution::direct(),
            'https://example.com/landing',
            $occurredAt,
        );

        $clickRepository = $this->createMock(ClickRepository::class);
        $revisitRepository = $this->createMock(RevisitRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);
        $clickUuidGenerator = $this->createMock(UuidGenerator::class);
        $visitUuidGenerator = $this->createMock(UuidGenerator::class);
        $transactionManager = $this->createMock(TransactionManager::class);

        $transactionManager
            ->expects($this->once())
            ->method('run')
            ->with($this->isInstanceOf(\Closure::class))
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $clickRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Click $click) use ($expectedAttribution): bool {
                return $click->id()->equals(new ClickId('click-123'))
                    && $click->visitId()?->equals(new VisitId('visit-789')) === true
                    && $click->attribution()->equals($expectedAttribution);
            }));

        $revisitRepository
            ->expects($this->never())
            ->method('save');

        $clickUuidGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('click-123');

        $visitUuidGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('visit-789');

        $visitRepository
            ->expects($this->exactly(2))
            ->method('findLastByVisitorId')
            ->with($command->visitorId)
            ->willReturn(null);

        $visitRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Visit $visit) use ($command, $occurredAt, $expectedAttribution): bool {
                return $visit->id()->equals(new VisitId('visit-789'))
                    && $visit->visitorId()->equals($command->visitorId)
                    && $visit->firstAttribution()->equals($expectedAttribution)
                    && $visit->lastAttribution()->equals($expectedAttribution)
                    && $visit->landingUrl() === $command->landingUrl
                    && $visit->startedAt() == $occurredAt
                    && $visit->lastTouchedAt() == $occurredAt;
            }));

        $action = new RegisterClickAction(
            $clickRepository,
            $revisitRepository,
            new ContinueCurrentVisitAction($visitRepository),
            new ResolveCurrentVisitAction($visitRepository, new VisitSessionRule, $visitUuidGenerator),
            $clickUuidGenerator,
            $transactionManager,
        );

        $result = $action($command);

        $this->assertInstanceOf(Visit::class, $result);
        $this->assertTrue($result->id()->equals(new VisitId('visit-789')));
        $this->assertTrue($result->visitorId()->equals($command->visitorId));
    }
}
