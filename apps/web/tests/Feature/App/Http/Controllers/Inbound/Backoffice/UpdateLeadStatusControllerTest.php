<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Backoffice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Tests\TestCase;

final class UpdateLeadStatusControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_the_lead_status_and_redirects_back_to_the_status_form(): void
    {
        $this->createLead('lead-123', 'new');

        $this->patch(route('admin.leads.status.update', ['leadId' => 'lead-123']), [
            'status' => 'qualified',
        ])
            ->assertRedirect(route('admin.leads.show', ['leadId' => 'lead-123']).'#lead-status-form')
            ->assertSessionHas('success', 'Статус збережено.')
            ->assertSessionHas('success_context', 'lead_status');

        $this->assertDatabaseHas('leads', [
            'id' => 'lead-123',
            'status' => 'qualified',
        ]);

        $this->assertDatabaseHas('lead_status_transitions', [
            'lead_id' => 'lead-123',
            'from_status' => 'new',
            'to_status' => 'qualified',
            'rule_key' => 'backoffice.manual_change',
        ]);
    }

    public function test_it_redirects_back_to_the_status_form_when_validation_fails(): void
    {
        $this->createLead('lead-123', 'new');

        $this->patch(route('admin.leads.status.update', ['leadId' => 'lead-123']), [
            'status' => 'invalid-status',
        ])
            ->assertRedirect(route('admin.leads.show', ['leadId' => 'lead-123']).'#lead-status-form')
            ->assertInvalid([
                'status' => 'Оберіть коректний статус ліда.',
            ]);

        $this->assertDatabaseHas('leads', [
            'id' => 'lead-123',
            'status' => 'new',
        ]);

        $this->assertDatabaseCount('lead_status_transitions', 0);
    }

    public function test_it_is_idempotent_when_the_selected_status_is_already_current(): void
    {
        $this->createLead('lead-123', 'qualified');

        $this->patch(route('admin.leads.status.update', ['leadId' => 'lead-123']), [
            'status' => 'qualified',
        ])
            ->assertRedirect(route('admin.leads.show', ['leadId' => 'lead-123']).'#lead-status-form')
            ->assertSessionHas('success', 'Статус збережено.')
            ->assertSessionHas('success_context', 'lead_status');

        $this->assertDatabaseHas('leads', [
            'id' => 'lead-123',
            'status' => 'qualified',
        ]);

        $this->assertDatabaseCount('lead_status_transitions', 0);
    }

    public function test_it_redirects_to_the_leads_list_when_the_lead_is_missing(): void
    {
        $this->patch(route('admin.leads.status.update', ['leadId' => 'missing-lead']), [
            'status' => 'qualified',
        ])
            ->assertRedirect(route('admin.leads.index'))
            ->assertSessionHas('error', 'Не вдалося змінити статус: лід не знайдено.');
    }

    private function createLead(string $leadId, string $status): void
    {
        LeadModel::query()->create([
            'id' => $leadId,
            'visitor_id' => 'visitor-123',
            'visit_id' => 'visit-123',
            'name' => 'Ірина',
            'phone' => '+380501112233',
            'status' => $status,
            'origin' => 'form',
            'created_at' => '2026-03-28 12:00:00',
            'visit_attribution_source' => 'google',
            'visit_attribution_medium' => 'cpc',
            'visit_attribution_campaign' => 'spring-sale',
        ]);
    }
}
