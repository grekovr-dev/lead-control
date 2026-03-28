<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Actions\Backoffice\ChangeLeadStatus;

use DateTimeImmutable;
use Inbound\Application\Actions\Backoffice\ChangeLeadStatus\ChangeLeadStatusAction;
use Inbound\Application\Actions\Backoffice\ChangeLeadStatus\ChangeLeadStatusCommand;
use Inbound\Application\Actions\Backoffice\ChangeLeadStatus\LeadNotFoundException;
use Inbound\Domain\Lead\Lead;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Lead\LeadRepository;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\LeadStatusHistory\LeadStatusTransition;
use Inbound\Domain\LeadStatusHistory\LeadStatusTransitionRepository;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\VisitId;
use PHPUnit\Framework\TestCase;

final class ChangeLeadStatusActionTest extends TestCase
{
    public function test_it_changes_status_for_an_existing_lead(): void
    {
        $command = new ChangeLeadStatusCommand(
            new LeadId('lead-123'),
            LeadStatus::QUALIFIED,
            'qualified_after_call',
            new DateTimeImmutable('2026-03-28T12:00:00+02:00'),
        );

        $lead = $this->makeLead($command->leadId, LeadStatus::NEW);
        $leadRepository = $this->createMock(LeadRepository::class);
        $leadStatusTransitionRepository = $this->createMock(LeadStatusTransitionRepository::class);

        $leadRepository
            ->expects($this->once())
            ->method('findById')
            ->with($command->leadId)
            ->willReturn($lead);

        $leadRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (Lead $savedLead) use ($command, $lead): bool {
                return $savedLead === $lead
                    && $savedLead->id()->equals($command->leadId)
                    && $savedLead->status() === LeadStatus::QUALIFIED;
            }));

        $leadStatusTransitionRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (LeadStatusTransition $transition) use ($command, $lead): bool {
                return $transition->leadId()->equals($lead->id())
                    && $transition->fromStatus() === LeadStatus::NEW
                    && $transition->toStatus() === LeadStatus::QUALIFIED
                    && $transition->ruleKey() === 'qualified_after_call'
                    && $transition->changedAt() == $command->changedAt;
            }));

        $action = new ChangeLeadStatusAction($leadRepository, $leadStatusTransitionRepository);

        $result = $action($command);

        $this->assertSame($lead, $result);
        $this->assertSame(LeadStatus::QUALIFIED, $result->status());
    }

    public function test_it_rejects_a_missing_lead(): void
    {
        $command = new ChangeLeadStatusCommand(
            new LeadId('lead-123'),
            LeadStatus::LOST,
            'lost_no_response',
            new DateTimeImmutable('2026-03-28T12:00:00+02:00'),
        );

        $leadRepository = $this->createMock(LeadRepository::class);
        $leadStatusTransitionRepository = $this->createMock(LeadStatusTransitionRepository::class);

        $leadRepository
            ->expects($this->once())
            ->method('findById')
            ->with($command->leadId)
            ->willReturn(null);

        $leadRepository
            ->expects($this->never())
            ->method('save');

        $leadStatusTransitionRepository
            ->expects($this->never())
            ->method('save');

        $action = new ChangeLeadStatusAction($leadRepository, $leadStatusTransitionRepository);

        $this->expectException(LeadNotFoundException::class);

        $action($command);
    }

    public function test_it_is_idempotent_when_status_is_already_current(): void
    {
        $command = new ChangeLeadStatusCommand(
            new LeadId('lead-123'),
            LeadStatus::QUALIFIED,
            'manual_backoffice',
            new DateTimeImmutable('2026-03-28T12:00:00+02:00'),
        );

        $lead = $this->makeLead($command->leadId, LeadStatus::QUALIFIED);
        $leadRepository = $this->createMock(LeadRepository::class);
        $leadStatusTransitionRepository = $this->createMock(LeadStatusTransitionRepository::class);

        $leadRepository
            ->expects($this->once())
            ->method('findById')
            ->with($command->leadId)
            ->willReturn($lead);

        $leadRepository
            ->expects($this->never())
            ->method('save');

        $leadStatusTransitionRepository
            ->expects($this->never())
            ->method('save');

        $action = new ChangeLeadStatusAction($leadRepository, $leadStatusTransitionRepository);

        $result = $action($command);

        $this->assertSame($lead, $result);
        $this->assertSame(LeadStatus::QUALIFIED, $result->status());
    }

    private function makeLead(LeadId $leadId, LeadStatus $status): Lead
    {
        return new Lead(
            $leadId,
            new VisitorId('visitor-456'),
            new VisitId('visit-789'),
            'John Doe',
            '+380501112233',
            Attribution::empty(),
            $status,
            'form',
            new DateTimeImmutable('2026-03-28T11:45:00+02:00'),
        );
    }
}
