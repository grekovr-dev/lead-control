<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\LeadStatusHistory\LeadStatusTransition;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadStatusTransitionRepository;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadStatusTransitionModel;
use Tests\TestCase;

final class EloquentLeadStatusTransitionRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_persists_a_status_transition(): void
    {
        $this->createLead('lead-123');

        $repository = new EloquentLeadStatusTransitionRepository();
        $transition = new LeadStatusTransition(
            new LeadId('lead-123'),
            LeadStatus::NEW,
            LeadStatus::CONTACTED,
            'manual_backoffice',
            new DateTimeImmutable('2026-03-28 12:00:00'),
        );

        $repository->save($transition);

        $this->assertDatabaseCount('lead_status_transitions', 1);
        $this->assertDatabaseHas('lead_status_transitions', [
            'lead_id' => 'lead-123',
            'from_status' => 'new',
            'to_status' => 'contacted',
            'rule_key' => 'manual_backoffice',
            'changed_at' => '2026-03-28 12:00:00',
        ]);
    }

    public function test_it_returns_transitions_for_a_lead_in_chronological_order(): void
    {
        $this->createLead('lead-123');
        $this->createLead('lead-other');

        LeadStatusTransitionModel::query()->create([
            'lead_id' => 'lead-123',
            'from_status' => 'contacted',
            'to_status' => 'qualified',
            'rule_key' => 'qualified_after_call',
            'changed_at' => '2026-03-28 12:10:00',
        ]);

        LeadStatusTransitionModel::query()->create([
            'lead_id' => 'lead-other',
            'from_status' => 'new',
            'to_status' => 'lost',
            'rule_key' => 'lost_spam',
            'changed_at' => '2026-03-28 12:05:00',
        ]);

        LeadStatusTransitionModel::query()->create([
            'lead_id' => 'lead-123',
            'from_status' => 'new',
            'to_status' => 'contacted',
            'rule_key' => 'manual_backoffice',
            'changed_at' => '2026-03-28 12:00:00',
        ]);

        $repository = new EloquentLeadStatusTransitionRepository();

        $transitions = $repository->findByLeadId(new LeadId('lead-123'));

        $this->assertCount(2, $transitions);
        $this->assertSame(LeadStatus::NEW, $transitions[0]->fromStatus());
        $this->assertSame(LeadStatus::CONTACTED, $transitions[0]->toStatus());
        $this->assertSame('manual_backoffice', $transitions[0]->ruleKey());
        $this->assertSame('2026-03-28 12:00:00', $transitions[0]->changedAt()->format('Y-m-d H:i:s'));
        $this->assertSame(LeadStatus::CONTACTED, $transitions[1]->fromStatus());
        $this->assertSame(LeadStatus::QUALIFIED, $transitions[1]->toStatus());
        $this->assertSame('qualified_after_call', $transitions[1]->ruleKey());
        $this->assertSame('2026-03-28 12:10:00', $transitions[1]->changedAt()->format('Y-m-d H:i:s'));
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
}
