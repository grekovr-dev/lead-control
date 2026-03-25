<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Actions\Capture\CapturePhoneClick;

use DateInterval;
use DateTimeImmutable;
use Inbound\Application\Actions\Capture\CapturePhoneClick\ActiveVisitNotFoundException;
use Inbound\Application\Actions\Capture\CapturePhoneClick\CapturePhoneClickAction;
use Inbound\Application\Actions\Capture\CapturePhoneClick\CapturePhoneClickCommand;
use Inbound\Application\Actions\Capture\ResolveVisitForCapture\VisitSessionRule;
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
    public function test_it_creates_lead_when_phone_click_lead_is_missing_in_active_visit(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-25T13:10:00+02:00');
        $command = new CapturePhoneClickCommand(
            new LeadId('lead-123'),
            new TouchId('touch-123'),
            new VisitorId('visitor-456'),
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            $occurredAt,
        );

        $existingVisit = new Visit(
            new VisitId('visit-existing'),
            $command->visitorId,
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            new Attribution('google', 'remarketing', null, null, null, null, null, null),
            new DateTimeImmutable('2026-03-25T13:00:00+02:00'),
            new DateTimeImmutable('2026-03-25T13:05:00+02:00'),
        );

        $leadRepository = $this->createMock(LeadRepository::class);
        $touchRepository = $this->createMock(TouchRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);

        $visitRepository
            ->expects($this->once())
            ->method('findLastByVisitorId')
            ->with($command->visitorId)
            ->willReturn($existingVisit);

        $leadRepository
            ->expects($this->once())
            ->method('findByVisitIdAndOrigin')
            ->with($existingVisit->id(), 'phone_click')
            ->willReturn(null);

        $leadRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Lead $lead) use ($command, $existingVisit, $occurredAt): bool {
                return $lead->id()->equals($command->leadId)
                    && $lead->visitorId()->equals($command->visitorId)
                    && $lead->visitId()->equals($existingVisit->id())
                    && $lead->name() === null
                    && $lead->phone() === null
                    && $lead->attribution()->equals($command->attribution)
                    && $lead->status() === LeadStatus::NEW
                    && $lead->origin() === 'phone_click'
                    && $lead->createdAt() == $occurredAt;
            }));

        $touchRepository
            ->expects($this->never())
            ->method('save');

        $visitRepository
            ->expects($this->never())
            ->method('save');

        $action = new CapturePhoneClickAction(
            $leadRepository,
            $touchRepository,
            $visitRepository,
            new VisitSessionRule(new DateInterval('PT30M')),
        );

        $result = $action($command);

        $this->assertInstanceOf(Lead::class, $result);
        $this->assertTrue($result->id()->equals($command->leadId));
        $this->assertTrue($result->visitId()->equals($existingVisit->id()));
    }

    public function test_it_registers_phone_click_touch_when_phone_click_lead_already_exists(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-25T13:10:00+02:00');
        $command = new CapturePhoneClickCommand(
            new LeadId('lead-new'),
            new TouchId('touch-123'),
            new VisitorId('visitor-456'),
            new Attribution('google', 'remarketing', null, null, null, null, null, null),
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
        );

        $leadRepository = $this->createMock(LeadRepository::class);
        $touchRepository = $this->createMock(TouchRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);

        $visitRepository
            ->expects($this->once())
            ->method('findLastByVisitorId')
            ->with($command->visitorId)
            ->willReturn($existingVisit);

        $leadRepository
            ->expects($this->once())
            ->method('findByVisitIdAndOrigin')
            ->with($existingVisit->id(), 'phone_click')
            ->willReturn($existingPhoneClickLead);

        $leadRepository
            ->expects($this->never())
            ->method('save');

        $visitRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Visit $visit) use ($command, $occurredAt, $existingVisit): bool {
                return $visit === $existingVisit
                    && $visit->lastAttribution()->equals($command->attribution)
                    && $visit->lastTouchedAt() == $occurredAt;
            }));

        $touchRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Touch $touch) use ($command, $existingVisit, $occurredAt): bool {
                return $touch->id()->equals($command->touchId)
                    && $touch->visitId()->equals($existingVisit->id())
                    && $touch->visitorId()->equals($command->visitorId)
                    && $touch->type() === TouchType::PhoneClick
                    && $touch->occurredAt() == $occurredAt;
            }));

        $action = new CapturePhoneClickAction(
            $leadRepository,
            $touchRepository,
            $visitRepository,
            new VisitSessionRule(new DateInterval('PT30M')),
        );

        $result = $action($command);

        $this->assertInstanceOf(Touch::class, $result);
        $this->assertTrue($result->id()->equals($command->touchId));
        $this->assertTrue($result->visitId()->equals($existingVisit->id()));
        $this->assertSame(TouchType::PhoneClick, $result->type());
    }

    public function test_it_creates_phone_click_lead_when_only_form_lead_exists_in_active_visit(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-25T13:10:00+02:00');
        $command = new CapturePhoneClickCommand(
            new LeadId('lead-123'),
            new TouchId('touch-123'),
            new VisitorId('visitor-456'),
            new Attribution('google', 'remarketing', null, null, null, null, null, null),
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

        $existingFormLead = new Lead(
            new LeadId('lead-form-existing'),
            $command->visitorId,
            $existingVisit->id(),
            'John Doe',
            '+380501112233',
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            LeadStatus::NEW,
            'form',
            new DateTimeImmutable('2026-03-25T13:06:00+02:00'),
        );

        $leadRepository = $this->createMock(LeadRepository::class);
        $touchRepository = $this->createMock(TouchRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);

        $visitRepository
            ->expects($this->once())
            ->method('findLastByVisitorId')
            ->with($command->visitorId)
            ->willReturn($existingVisit);

        $leadRepository
            ->expects($this->once())
            ->method('findByVisitIdAndOrigin')
            ->with($existingVisit->id(), 'phone_click')
            ->willReturn(null);

        $leadRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Lead $lead) use ($command, $existingVisit, $occurredAt, $existingFormLead): bool {
                return $lead->id()->equals($command->leadId)
                    && $lead->visitorId()->equals($command->visitorId)
                    && $lead->visitId()->equals($existingVisit->id())
                    && $lead->origin() === 'phone_click'
                    && $lead->createdAt() == $occurredAt
                    && $existingFormLead->origin() === 'form';
            }));

        $touchRepository
            ->expects($this->never())
            ->method('save');

        $visitRepository
            ->expects($this->never())
            ->method('save');

        $action = new CapturePhoneClickAction(
            $leadRepository,
            $touchRepository,
            $visitRepository,
            new VisitSessionRule(new DateInterval('PT30M')),
        );

        $result = $action($command);

        $this->assertInstanceOf(Lead::class, $result);
        $this->assertSame('phone_click', $result->origin());
        $this->assertTrue($result->visitId()->equals($existingVisit->id()));
    }

    public function test_it_throws_when_active_visit_is_missing(): void
    {
        $command = new CapturePhoneClickCommand(
            new LeadId('lead-123'),
            new TouchId('touch-123'),
            new VisitorId('visitor-456'),
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            new DateTimeImmutable('2026-03-25T13:10:00+02:00'),
        );

        $leadRepository = $this->createMock(LeadRepository::class);
        $touchRepository = $this->createMock(TouchRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);

        $visitRepository
            ->expects($this->once())
            ->method('findLastByVisitorId')
            ->with($command->visitorId)
            ->willReturn(null);

        $leadRepository
            ->expects($this->never())
            ->method('findByVisitIdAndOrigin');

        $leadRepository
            ->expects($this->never())
            ->method('save');

        $touchRepository
            ->expects($this->never())
            ->method('save');

        $action = new CapturePhoneClickAction(
            $leadRepository,
            $touchRepository,
            $visitRepository,
            new VisitSessionRule(new DateInterval('PT30M')),
        );

        $this->expectException(ActiveVisitNotFoundException::class);
        $this->expectExceptionMessage('Cannot capture phone click without an active visit.');

        $action($command);
    }
}
