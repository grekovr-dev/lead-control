<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Backoffice;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Tests\TestCase;

final class StoreLeadNoteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_a_note_and_redirects_back_to_the_lead_details_screen(): void
    {
        $user = User::factory()->create([
            'id' => 42,
        ]);

        $this->createLead('lead-123');

        $this->actingAs($user)
            ->from(route('admin.leads.show', ['leadId' => 'lead-123']))
            ->post(route('admin.leads.notes.store', ['leadId' => 'lead-123']), [
                'note' => ' Need to call back tomorrow. ',
            ])
            ->assertRedirect(route('admin.leads.show', ['leadId' => 'lead-123']).'#lead-note-form')
            ->assertSessionHas('success', 'Нотатку додано.');

        $this->assertDatabaseHas('lead_notes', [
            'lead_id' => 'lead-123',
            'author_id' => 42,
            'note' => 'Need to call back tomorrow.',
        ]);

        $this->actingAs($user)
            ->get(route('admin.leads.show', ['leadId' => 'lead-123']))
            ->assertOk()
            ->assertSeeText([
                'Додати нотатку',
                'Need to call back tomorrow.',
                'Автор #42',
            ]);
    }

    public function test_it_validates_the_note_and_redirects_back_to_the_details_screen(): void
    {
        $user = User::factory()->create([
            'id' => 42,
        ]);

        $this->createLead('lead-123');

        $this->actingAs($user)
            ->from(route('admin.leads.show', ['leadId' => 'lead-123']))
            ->post(route('admin.leads.notes.store', ['leadId' => 'lead-123']), [
                'note' => '   ',
            ])
            ->assertRedirect(route('admin.leads.show', ['leadId' => 'lead-123']).'#lead-note-form')
            ->assertInvalid([
                'note' => 'Поле нотатка є обов’язковим.',
            ]);

        $this->assertDatabaseCount('lead_notes', 0);
    }

    public function test_it_redirects_to_the_leads_list_when_the_lead_is_missing(): void
    {
        $user = User::factory()->create([
            'id' => 42,
        ]);

        $this->actingAs($user)
            ->from(route('admin.leads.index'))
            ->post(route('admin.leads.notes.store', ['leadId' => 'missing-lead']), [
                'note' => 'Need to call back tomorrow.',
            ])
            ->assertRedirect(route('admin.leads.index'))
            ->assertSessionHas('error', 'Не вдалося додати нотатку: лід не знайдено.');
    }

    private function createLead(string $leadId): void
    {
        LeadModel::query()->create([
            'id' => $leadId,
            'visitor_id' => 'visitor-123',
            'visit_id' => 'visit-123',
            'name' => 'Ірина',
            'phone' => '+380501112233',
            'status' => 'new',
            'origin' => 'form',
            'created_at' => '2026-03-28 12:00:00',
            'visit_attribution_source' => 'google',
            'visit_attribution_medium' => 'cpc',
            'visit_attribution_campaign' => 'spring-sale',
        ]);
    }
}
