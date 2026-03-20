<?php

declare(strict_types=1);

namespace Tests\Unit\Inbound\Application\Actions\Capture\CreateLeadFromPhoneClick;

use DateTimeImmutable;
use Inbound\Application\Actions\Capture\CreateLeadFromPhoneClick\ActiveVisitNotFoundException;
use Inbound\Application\Actions\Capture\CreateLeadFromPhoneClick\CreateLeadFromPhoneClickAction;
use Inbound\Application\Actions\Capture\CreateLeadFromPhoneClick\CreateLeadFromPhoneClickCommand;
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

final class CreateLeadFromPhoneClickActionTest extends TestCase
{
    public function test_it_creates_lead_using_existing_active_visit(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-20T13:10:00+02:00');
        $command = new CreateLeadFromPhoneClickCommand(
            new LeadId('lead-123'),
            new VisitorId('visitor-456'),
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            $occurredAt,
            '+380501112233',
        );

        $existingVisit = new Visit(
            new VisitId('visit-existing'),
            $command->visitorId,
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            new Attribution('google', 'remarketing', null, null, null, null, null, null),
            new DateTimeImmutable('2026-03-20T13:00:00+02:00'),
            new DateTimeImmutable('2026-03-20T13:05:00+02:00'),
        );

        $leadRepository = $this->createMock(LeadRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);

        $visitRepository
            ->expects($this->once())
            ->method('findActiveByVisitorId')
            ->with($command->visitorId)
            ->willReturn($existingVisit);

        $leadRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Lead $lead) use ($command, $existingVisit, $occurredAt): bool {
                return $lead->id()->equals($command->leadId)
                    && $lead->visitorId()->equals($command->visitorId)
                    && $lead->visitId()->equals($existingVisit->id())
                    && $lead->name() === null
                    && $lead->phone() === '+380501112233'
                    && $lead->attribution()->equals($command->attribution)
                    && $lead->status() === LeadStatus::NEW
                    && $lead->origin() === 'phone_click'
                    && $lead->createdAt() == $occurredAt;
            }));

        $action = new CreateLeadFromPhoneClickAction($leadRepository, $visitRepository);

        $result = $action($command);

        $this->assertInstanceOf(Lead::class, $result);
        $this->assertTrue($result->id()->equals($command->leadId));
        $this->assertTrue($result->visitId()->equals($existingVisit->id()));
    }

    public function test_it_throws_when_active_visit_is_missing(): void
    {
        $command = new CreateLeadFromPhoneClickCommand(
            new LeadId('lead-123'),
            new VisitorId('visitor-456'),
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            new DateTimeImmutable('2026-03-20T13:10:00+02:00'),
            '+380501112233',
        );

        $leadRepository = $this->createMock(LeadRepository::class);
        $visitRepository = $this->createMock(VisitRepository::class);

        $visitRepository
            ->expects($this->once())
            ->method('findActiveByVisitorId')
            ->with($command->visitorId)
            ->willReturn(null);

        $leadRepository
            ->expects($this->never())
            ->method('save');

        $action = new CreateLeadFromPhoneClickAction($leadRepository, $visitRepository);

        $this->expectException(ActiveVisitNotFoundException::class);
        $this->expectExceptionMessage('Cannot create lead from phone click without an active visit.');

        $action($command);
    }
}
