<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Actions\Capture\CreateLeadFromForm;

use DateTimeImmutable;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\ContinueCurrentVisitAction;
use Inbound\Application\Actions\Capture\CreateLeadFromForm\CreateLeadFromFormAction;
use Inbound\Application\Actions\Capture\CreateLeadFromForm\CreateLeadFromFormCommand;
use Inbound\Application\Actions\Capture\CreateLeadFromForm\CurrentVisitNotFoundException;
use Inbound\Application\Events\EventBus;
use Inbound\Application\Identifiers\UuidGenerator;
use Inbound\Application\Transactions\TransactionManager;
use Inbound\Domain\Lead\Events\LeadCreated;
use Inbound\Domain\Lead\Lead;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Lead\LeadRepository;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\Visit;
use Inbound\Domain\Visit\VisitId;
use Inbound\Domain\Visit\VisitRepository;
use PHPUnit\Framework\TestCase;

final class CreateLeadFromFormActionTest extends TestCase
{
    public function test_it_creates_lead_using_existing_visit(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-20T12:10:00+02:00');
        $expectedLeadId = new LeadId('lead-123');
        $command = new CreateLeadFromFormCommand(
            new VisitorId('visitor-456'),
            'John Doe',
            '+380501112233',
            $occurredAt,
        );

        $firstVisit = new Visit(
            new VisitId('visit-first'),
            $command->visitorId,
            new Attribution('facebook', 'paid-social', null, null, null, null, null, null),
            new Attribution('facebook', 'paid-social', null, null, null, null, null, null),
            new DateTimeImmutable('2026-03-20T10:00:00+02:00'),
            new DateTimeImmutable('2026-03-20T10:10:00+02:00'),
            'https://example.com/first-landing',
        );

        $existingVisit = new Visit(
            new VisitId('visit-existing'),
            $command->visitorId,
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            new Attribution('google', 'remarketing', null, null, null, null, null, null),
            new DateTimeImmutable('2026-03-20T12:00:00+02:00'),
            new DateTimeImmutable('2026-03-20T12:05:00+02:00'),
            'https://example.com/form-landing',
        );

        $leadRepository = $this->createMock(LeadRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);
        $uuidGenerator = $this->createMock(UuidGenerator::class);
        $eventBus = $this->createMock(EventBus::class);
        $transactionManager = $this->createMock(TransactionManager::class);

        $uuidGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn($expectedLeadId->value());

        $transactionManager
            ->expects($this->once())
            ->method('run')
            ->with($this->isInstanceOf(\Closure::class))
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());

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

        $visitRepository
            ->expects($this->once())
            ->method('findFirstByVisitorId')
            ->with($command->visitorId)
            ->willReturn($firstVisit);

        $leadRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Lead $lead) use ($expectedLeadId, $command, $existingVisit, $firstVisit, $occurredAt): bool {
                return $lead->id()->equals($expectedLeadId)
                    && $lead->visitorId()->equals($command->visitorId)
                    && $lead->visitId()->equals($existingVisit->id())
                    && $lead->name() === 'John Doe'
                    && $lead->phone() === '+380501112233'
                    && $lead->visitAttribution()->equals($existingVisit->firstAttribution())
                    && $lead->visitorAttribution()->equals($firstVisit->firstAttribution())
                    && $lead->landingUrl() === 'https://example.com/form-landing'
                    && $lead->status() === LeadStatus::NEW
                    && $lead->origin() === 'form'
                    && $lead->createdAt() == $occurredAt;
            }));

        $eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (object $event) use ($expectedLeadId, $occurredAt): bool {
                return $event instanceof LeadCreated
                    && $event->leadId->equals($expectedLeadId)
                    && $event->occurredAt == $occurredAt;
            }));

        $action = new CreateLeadFromFormAction(
            $leadRepository,
            $visitRepository,
            new ContinueCurrentVisitAction($visitRepository),
            $uuidGenerator,
            $eventBus,
            $transactionManager,
        );

        $result = $action($command);

        $this->assertInstanceOf(Lead::class, $result);
        $this->assertTrue($result->id()->equals($expectedLeadId));
        $this->assertTrue($result->visitId()->equals($existingVisit->id()));
    }

    public function test_it_throws_when_current_visit_is_missing(): void
    {
        $command = new CreateLeadFromFormCommand(
            new VisitorId('visitor-456'),
            'John Doe',
            '+380501112233',
            new DateTimeImmutable('2026-03-20T12:10:00+02:00'),
        );

        $leadRepository = $this->createMock(LeadRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);
        $uuidGenerator = $this->createMock(UuidGenerator::class);
        $eventBus = $this->createMock(EventBus::class);
        $transactionManager = $this->createMock(TransactionManager::class);

        $uuidGenerator
            ->expects($this->never())
            ->method('generate');

        $transactionManager
            ->expects($this->once())
            ->method('run')
            ->with($this->isInstanceOf(\Closure::class))
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $visitRepository
            ->expects($this->once())
            ->method('findLastByVisitorId')
            ->with($command->visitorId)
            ->willReturn(null);

        $visitRepository
            ->expects($this->never())
            ->method('save');

        $visitRepository
            ->expects($this->never())
            ->method('findFirstByVisitorId');

        $leadRepository
            ->expects($this->never())
            ->method('save');

        $eventBus
            ->expects($this->never())
            ->method('publish');

        $action = new CreateLeadFromFormAction(
            $leadRepository,
            $visitRepository,
            new ContinueCurrentVisitAction($visitRepository),
            $uuidGenerator,
            $eventBus,
            $transactionManager,
        );

        $this->expectException(CurrentVisitNotFoundException::class);
        $this->expectExceptionMessage('Cannot create lead from form without a current visit.');

        $action($command);
    }

    public function test_it_continues_last_visit_even_when_it_is_expired_by_session_rule(): void
    {
        $expectedLeadId = new LeadId('lead-123');
        $command = new CreateLeadFromFormCommand(
            new VisitorId('visitor-456'),
            'John Doe',
            '+380501112233',
            new DateTimeImmutable('2026-03-20T12:40:01+02:00'),
        );

        $expiredVisit = new Visit(
            new VisitId('visit-existing'),
            $command->visitorId,
            Attribution::empty(),
            Attribution::empty(),
            new DateTimeImmutable('2026-03-20T12:00:00+02:00'),
            new DateTimeImmutable('2026-03-20T12:10:00+02:00'),
        );

        $leadRepository = $this->createMock(LeadRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);
        $uuidGenerator = $this->createMock(UuidGenerator::class);
        $eventBus = $this->createMock(EventBus::class);
        $transactionManager = $this->createMock(TransactionManager::class);

        $uuidGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn($expectedLeadId->value());

        $transactionManager
            ->expects($this->once())
            ->method('run')
            ->with($this->isInstanceOf(\Closure::class))
            ->willReturnCallback(static fn (callable $callback): mixed => $callback());

        $visitRepository
            ->expects($this->once())
            ->method('findLastByVisitorId')
            ->with($command->visitorId)
            ->willReturn($expiredVisit);

        $visitRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Visit $visit) use ($expiredVisit, $command): bool {
                return $visit === $expiredVisit
                    && $visit->lastTouchedAt() == $command->occurredAt;
            }));

        $visitRepository
            ->expects($this->once())
            ->method('findFirstByVisitorId')
            ->with($command->visitorId)
            ->willReturn($expiredVisit);

        $leadRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Lead $lead) use ($expectedLeadId): bool {
                return $lead->id()->equals($expectedLeadId);
            }));

        $eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->isInstanceOf(LeadCreated::class));

        $action = new CreateLeadFromFormAction(
            $leadRepository,
            $visitRepository,
            new ContinueCurrentVisitAction($visitRepository),
            $uuidGenerator,
            $eventBus,
            $transactionManager,
        );

        $result = $action($command);

        $this->assertInstanceOf(Lead::class, $result);
        $this->assertTrue($result->visitId()->equals($expiredVisit->id()));
    }
}
