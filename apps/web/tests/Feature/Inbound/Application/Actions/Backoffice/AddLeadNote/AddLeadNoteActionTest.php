<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Application\Actions\Backoffice\AddLeadNote;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inbound\Application\Actions\Backoffice\AddLeadNote\AddLeadNoteAction;
use Inbound\Application\Actions\Backoffice\AddLeadNote\AddLeadNoteCommand;
use Inbound\Application\Actions\Backoffice\AddLeadNote\LeadNotFoundException;
use Inbound\Domain\Lead\LeadId;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadNoteRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadRepository;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Tests\TestCase;

final class AddLeadNoteActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_a_note_for_an_existing_lead(): void
    {
        $this->createUser(42);

        LeadModel::query()->create([
            'id' => 'lead-123',
            'visitor_id' => 'visitor-456',
            'visit_id' => 'visit-789',
            'name' => 'John Doe',
            'phone' => '+380501112233',
            'status' => 'new',
            'origin' => 'form',
            'created_at' => '2026-03-28 11:45:00',
        ]);

        $action = new AddLeadNoteAction(
            new EloquentLeadRepository(),
            new EloquentLeadNoteRepository(),
        );

        $leadNote = $action(new AddLeadNoteCommand(
            new LeadId('lead-123'),
            42,
            ' Need to call back tomorrow. ',
            new DateTimeImmutable('2026-03-28 12:00:00'),
        ));

        $this->assertSame('lead-123', $leadNote->leadId()->value());
        $this->assertSame(42, $leadNote->authorId());
        $this->assertSame('Need to call back tomorrow.', $leadNote->note());

        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => 'lead-123',
            'author_id' => 42,
            'note' => 'Need to call back tomorrow.',
            'created_at' => '2026-03-28 12:00:00',
            'updated_at' => '2026-03-28 12:00:00',
        ]);
    }

    public function test_it_rejects_a_missing_lead(): void
    {
        $action = new AddLeadNoteAction(
            new EloquentLeadRepository(),
            new EloquentLeadNoteRepository(),
        );

        $this->expectException(LeadNotFoundException::class);

        $action(new AddLeadNoteCommand(
            new LeadId('lead-123'),
            42,
            'Need to clarify project timeline.',
            new DateTimeImmutable('2026-03-28 12:00:00'),
        ));
    }

    private function createUser(int $id): void
    {
        DB::table('users')->insert([
            'id' => $id,
            'name' => 'Test User '.$id,
            'email' => 'user'.$id.'@example.test',
            'password' => 'password',
            'created_at' => '2026-03-28 11:00:00',
            'updated_at' => '2026-03-28 11:00:00',
        ]);
    }
}
