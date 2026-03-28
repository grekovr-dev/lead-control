<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Application\Actions\Backoffice\ChangeLeadStatus;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Actions\Backoffice\ChangeLeadStatus\ChangeLeadStatusAction;
use Inbound\Application\Actions\Backoffice\ChangeLeadStatus\ChangeLeadStatusCommand;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadStatusTransitionRepository;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Tests\TestCase;

final class ChangeLeadStatusActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_lead_status_and_persists_transition_history(): void
    {
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

        $action = new ChangeLeadStatusAction(
            new EloquentLeadRepository(),
            new EloquentLeadStatusTransitionRepository(),
        );

        $lead = $action(new ChangeLeadStatusCommand(
            new LeadId('lead-123'),
            LeadStatus::QUALIFIED,
            'qualified_after_call',
            new DateTimeImmutable('2026-03-28 12:00:00'),
        ));

        $this->assertSame(LeadStatus::QUALIFIED, $lead->status());

        $this->assertDatabaseHas('leads', [
            'id' => 'lead-123',
            'status' => 'qualified',
        ]);

        $this->assertDatabaseHas('lead_status_transitions', [
            'lead_id' => 'lead-123',
            'from_status' => 'new',
            'to_status' => 'qualified',
            'rule_key' => 'qualified_after_call',
            'changed_at' => '2026-03-28 12:00:00',
        ]);
    }

    public function test_it_does_not_persist_transition_when_status_is_already_current(): void
    {
        LeadModel::query()->create([
            'id' => 'lead-123',
            'visitor_id' => 'visitor-456',
            'visit_id' => 'visit-789',
            'name' => 'John Doe',
            'phone' => '+380501112233',
            'status' => 'qualified',
            'origin' => 'form',
            'created_at' => '2026-03-28 11:45:00',
        ]);

        $action = new ChangeLeadStatusAction(
            new EloquentLeadRepository(),
            new EloquentLeadStatusTransitionRepository(),
        );

        $lead = $action(new ChangeLeadStatusCommand(
            new LeadId('lead-123'),
            LeadStatus::QUALIFIED,
            'manual_backoffice',
            new DateTimeImmutable('2026-03-28 12:00:00'),
        ));

        $this->assertSame(LeadStatus::QUALIFIED, $lead->status());
        $this->assertDatabaseCount('lead_status_transitions', 0);
    }
}
