<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\LeadNote\LeadNote;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadNoteRepository;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Tests\TestCase;

final class EloquentLeadNoteRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_a_lead_note(): void
    {
        $this->createLead('lead-123');
        $this->createUser(42);

        $repository = new EloquentLeadNoteRepository();
        $leadNote = new LeadNote(
            new LeadId('lead-123'),
            42,
            'Need to call back tomorrow.',
            new DateTimeImmutable('2026-03-28 12:00:00'),
        );

        $repository->save($leadNote);

        $this->assertDatabaseCount('lead_notes', 1);
        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => 'lead-123',
            'author_id' => 42,
            'note' => 'Need to call back tomorrow.',
            'created_at' => '2026-03-28 12:00:00',
            'updated_at' => '2026-03-28 12:00:00',
        ]);
    }

    public function test_it_returns_notes_for_a_lead_in_latest_first_order(): void
    {
        $this->createLead('lead-123');
        $this->createLead('lead-other');
        $this->createUser(7);
        $this->createUser(9);

        DB::table('lead_notes')->insert([
            'lead_id' => 'lead-123',
            'author_id' => 7,
            'note' => 'Older note',
            'created_at' => '2026-03-28 12:00:00',
            'updated_at' => '2026-03-28 12:00:00',
        ]);

        DB::table('lead_notes')->insert([
            'lead_id' => 'lead-other',
            'author_id' => 9,
            'note' => 'Other lead note',
            'created_at' => '2026-03-28 12:05:00',
            'updated_at' => '2026-03-28 12:05:00',
        ]);

        DB::table('lead_notes')->insert([
            'lead_id' => 'lead-123',
            'author_id' => null,
            'note' => 'Newest note',
            'created_at' => '2026-03-28 12:10:00',
            'updated_at' => '2026-03-28 12:10:00',
        ]);

        $repository = new EloquentLeadNoteRepository();

        $notes = $repository->findByLeadId(new LeadId('lead-123'));

        $this->assertCount(2, $notes);
        $this->assertSame('Newest note', $notes[0]->note());
        $this->assertNull($notes[0]->authorId());
        $this->assertSame('2026-03-28 12:10:00', $notes[0]->createdAt()->format('Y-m-d H:i:s'));
        $this->assertSame('Older note', $notes[1]->note());
        $this->assertSame(7, $notes[1]->authorId());
        $this->assertSame('2026-03-28 12:00:00', $notes[1]->createdAt()->format('Y-m-d H:i:s'));
    }

    private function createLead(string $leadId): void
    {
        LeadModel::query()->create([
            'id' => $leadId,
            'visitor_id' => 'visitor-456',
            'visit_id' => 'visit-789',
            'name' => 'John Doe',
            'phone' => '+380501112233',
            'status' => 'new',
            'origin' => 'form',
            'created_at' => '2026-03-28 11:45:00',
        ]);
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
