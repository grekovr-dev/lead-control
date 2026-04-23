<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Actions\Capture\PhoneClick;

use DateTimeImmutable;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\ContinueCurrentVisitAction;
use Inbound\Application\Actions\Capture\PhoneClick\CapturePhoneClickAction;
use Inbound\Application\Actions\Capture\PhoneClick\CapturePhoneClickCommand;
use Inbound\Application\Actions\Capture\PhoneClick\CapturePhoneClickResult;
use Inbound\Application\Actions\Capture\PhoneClick\CurrentVisitNotFoundException;
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
use Inbound\Domain\Touch\Touch;
use Inbound\Domain\Touch\TouchId;
use Inbound\Domain\Touch\TouchRepository;
use Inbound\Domain\Touch\TouchType;
use Inbound\Domain\Visit\Visit;
use Inbound\Domain\Visit\VisitId;
use Inbound\Domain\Visit\VisitRepository;
use PHPUnit\Framework\TestCase;

final class CapturePhoneClickActionTest extends TestCase
{
    public function test_it_creates_lead_when_phone_click_lead_is_missing_in_current_visit(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-25T13:10:00+02:00');
        $expectedLeadId = new LeadId('lead-123');
        $command = new CapturePhoneClickCommand(
            new VisitorId('visitor-456'),
            $occurredAt,
        );

        $firstVisit = new Visit(
            new VisitId('visit-first'),
            $command->visitorId,
            new Attribution('facebook', 'paid-social', null, null, null, null, null, null),
            new Attribution('facebook', 'paid-social', null, null, null, null, null, null),
            new DateTimeImmutable('2026-03-25T11:00:00+02:00'),
            new DateTimeImmutable('2026-03-25T11:10:00+02:00'),
            'https://example.com/first-landing',
        );

        $existingVisit = new Visit(
            new VisitId('visit-existing'),
            $command->visitorId,
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            new Attribution('google', 'remarketing', null, null, null, null, null, null),
            new DateTimeImmutable('2026-03-25T13:00:00+02:00'),
            new DateTimeImmutable('2026-03-25T13:05:00+02:00'),
            'https://example.com/phone-landing',
        );

        $leadRepository = $this->createMock(LeadRepository::class);
        $touchRepository = $this->createMock(TouchRepository::class);
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

        $leadRepository
            ->expects($this->once())
            ->method('findByVisitIdAndOrigin')
            ->with($existingVisit->id(), 'phone_click')
            ->willReturn(null);

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
                    && $lead->name() === null
                    && $lead->phone() === null
                    && $lead->visitAttribution()->equals($existingVisit->firstAttribution())
                    && $lead->visitorAttribution()->equals($firstVisit->firstAttribution())
                    && $lead->landingUrl() === 'https://example.com/phone-landing'
                    && $lead->status() === LeadStatus::NEW
                    && $lead->origin() === 'phone_click'
                    && $lead->createdAt() == $occurredAt;
            }));

        $touchRepository
            ->expects($this->never())
            ->method('save');

        $eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (object $event) use ($expectedLeadId, $occurredAt): bool {
                return $event instanceof LeadCreated
                    && $event->leadId->equals($expectedLeadId)
                    && $event->occurredAt == $occurredAt;
            }));

        $action = new CapturePhoneClickAction(
            $leadRepository,
            $touchRepository,
            $visitRepository,
            new ContinueCurrentVisitAction($visitRepository),
            $uuidGenerator,
            $eventBus,
            $transactionManager,
        );

        $result = $action($command);

        $this->assertInstanceOf(CapturePhoneClickResult::class, $result);
        $this->assertSame($command->visitorId->value(), $result->visitorId);
        $this->assertSame($existingVisit->id()->value(), $result->visitId);
        $this->assertSame(CapturePhoneClickResult::TYPE_LEAD, $result->resultType);
        $this->assertSame($expectedLeadId->value(), $result->resultId);
    }

    public function test_it_registers_phone_click_touch_when_phone_click_lead_already_exists(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-25T13:10:00+02:00');
        $expectedTouchId = new TouchId('touch-123');
        $command = new CapturePhoneClickCommand(
            new VisitorId('visitor-456'),
            $occurredAt,
        );

        $existingVisit = new Visit(
            new VisitId('visit-existing'),
            $command->visitorId,
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            new DateTimeImmutable('2026-03-25T13:00:00+02:00'),
            new DateTimeImmutable('2026-03-25T13:05:00+02:00'),
        );

        $existingPhoneClickLead = new Lead(
            new LeadId('lead-existing'),
            $command->visitorId,
            $existingVisit->id(),
            null,
            null,
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            LeadStatus::NEW,
            'phone_click',
            new DateTimeImmutable('2026-03-25T13:06:00+02:00'),
            new Attribution('google', 'cpc', null, null, null, null, null, null),
        );

        $leadRepository = $this->createMock(LeadRepository::class);
        $touchRepository = $this->createMock(TouchRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);
        $uuidGenerator = $this->createMock(UuidGenerator::class);
        $eventBus = $this->createMock(EventBus::class);
        $transactionManager = $this->createMock(TransactionManager::class);

        $uuidGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn($expectedTouchId->value());

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
                    && $visit->lastAttribution()->medium() === 'cpc'
                    && $visit->lastTouchedAt() == $occurredAt;
            }));

        $leadRepository
            ->expects($this->once())
            ->method('findByVisitIdAndOrigin')
            ->with($existingVisit->id(), 'phone_click')
            ->willReturn($existingPhoneClickLead);

        $visitRepository
            ->expects($this->never())
            ->method('findFirstByVisitorId');

        $leadRepository
            ->expects($this->never())
            ->method('save');

        $touchRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Touch $touch) use ($expectedTouchId, $existingVisit, $occurredAt): bool {
                return $touch->id()->equals($expectedTouchId)
                    && $touch->visitId()->equals($existingVisit->id())
                    && $touch->visitorId()->equals(new VisitorId('visitor-456'))
                    && $touch->type() === TouchType::PhoneClick
                    && $touch->occurredAt() == $occurredAt;
            }));

        $eventBus
            ->expects($this->never())
            ->method('publish');

        $action = new CapturePhoneClickAction(
            $leadRepository,
            $touchRepository,
            $visitRepository,
            new ContinueCurrentVisitAction($visitRepository),
            $uuidGenerator,
            $eventBus,
            $transactionManager,
        );

        $result = $action($command);

        $this->assertInstanceOf(CapturePhoneClickResult::class, $result);
        $this->assertSame($command->visitorId->value(), $result->visitorId);
        $this->assertSame($existingVisit->id()->value(), $result->visitId);
        $this->assertSame(CapturePhoneClickResult::TYPE_TOUCH, $result->resultType);
        $this->assertSame($expectedTouchId->value(), $result->resultId);
    }

    public function test_it_creates_phone_click_lead_when_only_form_lead_exists_in_current_visit(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-25T13:10:00+02:00');
        $expectedLeadId = new LeadId('lead-123');
        $command = new CapturePhoneClickCommand(
            new VisitorId('visitor-456'),
            $occurredAt,
        );

        $existingVisit = new Visit(
            new VisitId('visit-existing'),
            $command->visitorId,
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            new DateTimeImmutable('2026-03-25T13:00:00+02:00'),
            new DateTimeImmutable('2026-03-25T13:05:00+02:00'),
        );

        $leadRepository = $this->createMock(LeadRepository::class);
        $touchRepository = $this->createMock(TouchRepository::class);
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

        $leadRepository
            ->expects($this->once())
            ->method('findByVisitIdAndOrigin')
            ->with($existingVisit->id(), 'phone_click')
            ->willReturn(null);

        $visitRepository
            ->expects($this->once())
            ->method('findFirstByVisitorId')
            ->with($command->visitorId)
            ->willReturn($existingVisit);

        $leadRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Lead $lead) use ($expectedLeadId, $command, $existingVisit, $occurredAt): bool {
                return $lead->id()->equals($expectedLeadId)
                    && $lead->visitorId()->equals($command->visitorId)
                    && $lead->visitId()->equals($existingVisit->id())
                    && $lead->visitAttribution()->equals($existingVisit->firstAttribution())
                    && $lead->visitorAttribution()->equals($existingVisit->firstAttribution())
                    && $lead->origin() === 'phone_click'
                    && $lead->createdAt() == $occurredAt;
            }));

        $touchRepository
            ->expects($this->never())
            ->method('save');

        $eventBus
            ->expects($this->once())
            ->method('publish')
            ->with($this->callback(function (object $event) use ($expectedLeadId, $occurredAt): bool {
                return $event instanceof LeadCreated
                    && $event->leadId->equals($expectedLeadId)
                    && $event->occurredAt == $occurredAt;
            }));

        $action = new CapturePhoneClickAction(
            $leadRepository,
            $touchRepository,
            $visitRepository,
            new ContinueCurrentVisitAction($visitRepository),
            $uuidGenerator,
            $eventBus,
            $transactionManager,
        );

        $result = $action($command);

        $this->assertInstanceOf(CapturePhoneClickResult::class, $result);
        $this->assertSame($command->visitorId->value(), $result->visitorId);
        $this->assertSame($existingVisit->id()->value(), $result->visitId);
        $this->assertSame(CapturePhoneClickResult::TYPE_LEAD, $result->resultType);
        $this->assertSame($expectedLeadId->value(), $result->resultId);
    }

    public function test_it_throws_when_current_visit_is_missing(): void
    {
        $command = new CapturePhoneClickCommand(
            new VisitorId('visitor-456'),
            new DateTimeImmutable('2026-03-25T13:10:00+02:00'),
        );

        $leadRepository = $this->createMock(LeadRepository::class);
        $touchRepository = $this->createMock(TouchRepository::class);
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
            ->method('findByVisitIdAndOrigin');

        $leadRepository
            ->expects($this->never())
            ->method('save');

        $touchRepository
            ->expects($this->never())
            ->method('save');

        $eventBus
            ->expects($this->never())
            ->method('publish');

        $action = new CapturePhoneClickAction(
            $leadRepository,
            $touchRepository,
            $visitRepository,
            new ContinueCurrentVisitAction($visitRepository),
            $uuidGenerator,
            $eventBus,
            $transactionManager,
        );

        $this->expectException(CurrentVisitNotFoundException::class);
        $this->expectExceptionMessage('Cannot capture phone click without a current visit.');

        $action($command);
    }
}
