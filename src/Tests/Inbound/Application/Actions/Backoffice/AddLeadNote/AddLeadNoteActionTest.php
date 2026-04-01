<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Actions\Backoffice\AddLeadNote;

use DateTimeImmutable;
use Inbound\Application\Actions\Backoffice\AddLeadNote\AddLeadNoteAction;
use Inbound\Application\Actions\Backoffice\AddLeadNote\AddLeadNoteCommand;
use Inbound\Application\Actions\Backoffice\AddLeadNote\LeadNotFoundException;
use Inbound\Domain\Lead\Lead;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Lead\LeadRepository;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\LeadNote\LeadNote;
use Inbound\Domain\LeadNote\LeadNoteRepository;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\VisitId;
use PHPUnit\Framework\TestCase;

final class AddLeadNoteActionTest extends TestCase
{
    public function test_it_adds_a_note_to_an_existing_lead(): void
    {
        $createdAt = new DateTimeImmutable('2026-03-28T12:00:00+02:00');
        $command = new AddLeadNoteCommand(
            new LeadId('lead-123'),
            42,
            ' Need to call back tomorrow. ',
            $createdAt,
        );

        $lead = $this->makeLead($command->leadId);
        $leadRepository = $this->createMock(LeadRepository::class);
        $leadNoteRepository = $this->createMock(LeadNoteRepository::class);

        $leadRepository
            ->expects($this->once())
            ->method('findById')
            ->with($command->leadId)
            ->willReturn($lead);

        $leadNoteRepository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (LeadNote $leadNote) use ($lead, $createdAt): bool {
                return $leadNote->leadId()->equals($lead->id())
                    && $leadNote->authorId() === 42
                    && $leadNote->note() === 'Need to call back tomorrow.'
                    && $leadNote->createdAt() == $createdAt;
            }));

        $action = new AddLeadNoteAction($leadRepository, $leadNoteRepository);

        $result = $action($command);

        $this->assertInstanceOf(LeadNote::class, $result);
        $this->assertTrue($result->leadId()->equals($lead->id()));
        $this->assertSame(42, $result->authorId());
        $this->assertSame('Need to call back tomorrow.', $result->note());
    }

    public function test_it_rejects_a_missing_lead(): void
    {
        $command = new AddLeadNoteCommand(
            new LeadId('lead-123'),
            42,
            'Need to clarify project timeline.',
            new DateTimeImmutable('2026-03-28T12:00:00+02:00'),
        );

        $leadRepository = $this->createMock(LeadRepository::class);
        $leadNoteRepository = $this->createMock(LeadNoteRepository::class);

        $leadRepository
            ->expects($this->once())
            ->method('findById')
            ->with($command->leadId)
            ->willReturn(null);

        $leadNoteRepository
            ->expects($this->never())
            ->method('save');

        $action = new AddLeadNoteAction($leadRepository, $leadNoteRepository);

        $this->expectException(LeadNotFoundException::class);

        $action($command);
    }

    private function makeLead(LeadId $leadId): Lead
    {
        return new Lead(
            $leadId,
            new VisitorId('visitor-456'),
            new VisitId('visit-789'),
            'John Doe',
            '+380501112233',
            Attribution::empty(),
            LeadStatus::NEW,
            'form',
            new DateTimeImmutable('2026-03-28T11:45:00+02:00'),
            Attribution::empty(),
        );
    }
}
