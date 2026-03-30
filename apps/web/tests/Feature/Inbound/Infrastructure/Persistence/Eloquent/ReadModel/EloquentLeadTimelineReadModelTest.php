<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\GetLeadTimelineQuery;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\LeadTimelineNotFoundException;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadStatusTransitionModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentLeadTimelineReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\TouchModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use Inbound\Domain\Lead\LeadId;
use Tests\TestCase;

final class EloquentLeadTimelineReadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_reverse_chronological_timeline_for_a_lead(): void
    {
        $this->createUser(42);
        $this->createLead('lead-123', 'visitor-123', 'visit-123', '2026-03-28 12:00:00');
        $this->createLead('lead-other', 'visitor-other', 'visit-other', '2026-03-28 12:00:00');

        VisitModel::query()->create([
            'id' => 'visit-123',
            'visitor_id' => 'visitor-123',
            'started_at' => '2026-03-28 11:30:00',
            'last_touched_at' => '2026-03-28 12:20:00',
        ]);

        ClickModel::query()->create([
            'id' => 'click-123',
            'visitor_id' => 'visitor-123',
            'landing_url' => 'https://example.com/landing',
            'referrer' => 'https://google.com/',
            'occurred_at' => '2026-03-28 11:40:00',
        ]);

        ClickModel::query()->create([
            'id' => 'click-other',
            'visitor_id' => 'visitor-other',
            'landing_url' => 'https://example.com/other',
            'referrer' => null,
            'occurred_at' => '2026-03-28 11:45:00',
        ]);

        TouchModel::query()->create([
            'id' => 'touch-123',
            'visit_id' => 'visit-123',
            'visitor_id' => 'visitor-123',
            'type' => 'messenger_click',
            'occurred_at' => '2026-03-28 11:50:00',
        ]);

        TouchModel::query()->create([
            'id' => 'touch-other',
            'visit_id' => 'visit-other',
            'visitor_id' => 'visitor-other',
            'type' => 'lead_form_click',
            'occurred_at' => '2026-03-28 11:55:00',
        ]);

        LeadStatusTransitionModel::query()->create([
            'lead_id' => 'lead-123',
            'from_status' => 'new',
            'to_status' => 'contacted',
            'rule_key' => 'manual_backoffice',
            'changed_at' => '2026-03-28 12:10:00',
        ]);

        LeadStatusTransitionModel::query()->create([
            'lead_id' => 'lead-other',
            'from_status' => 'new',
            'to_status' => 'lost',
            'rule_key' => 'lost_spam',
            'changed_at' => '2026-03-28 12:15:00',
        ]);

        DB::table('lead_notes')->insert([
            'lead_id' => 'lead-123',
            'author_id' => 42,
            'note' => 'Need to call back tomorrow.',
            'created_at' => '2026-03-28 12:20:00',
            'updated_at' => '2026-03-28 12:20:00',
        ]);

        DB::table('lead_notes')->insert([
            'lead_id' => 'lead-other',
            'author_id' => null,
            'note' => 'Other lead note.',
            'created_at' => '2026-03-28 12:25:00',
            'updated_at' => '2026-03-28 12:25:00',
        ]);

        $readModel = new EloquentLeadTimelineReadModel();

        $timeline = $readModel(new GetLeadTimelineQuery(new LeadId('lead-123')));

        $this->assertSame('lead-123', $timeline->leadId);
        $this->assertCount(5, $timeline->events);
        $this->assertSame(['lead_note', 'status_transition', 'lead_created', 'touch', 'click'], array_map(
            static fn ($event): string => $event->type,
            $timeline->events,
        ));
        $this->assertSame(42, $timeline->events[0]->authorId);
        $this->assertSame('Need to call back tomorrow.', $timeline->events[0]->description);
        $this->assertSame('manual_backoffice', $timeline->events[1]->ruleKey);
        $this->assertSame('form', $timeline->events[2]->origin);
        $this->assertSame('messenger_click', $timeline->events[3]->touchType);
        $this->assertSame('https://example.com/landing', $timeline->events[4]->landingUrl);
    }

    public function test_it_throws_when_lead_is_missing(): void
    {
        $readModel = new EloquentLeadTimelineReadModel();

        $this->expectException(LeadTimelineNotFoundException::class);

        $readModel(new GetLeadTimelineQuery(new LeadId('missing-lead')));
    }

    private function createLead(string $leadId, string $visitorId, string $visitId, string $createdAt): void
    {
        LeadModel::query()->create([
            'id' => $leadId,
            'visitor_id' => $visitorId,
            'visit_id' => $visitId,
            'name' => 'John Doe',
            'phone' => '+380501112233',
            'status' => 'new',
            'origin' => 'form',
            'created_at' => $createdAt,
        ]);
    }

    private function createUser(int $id): void
    {
        DB::table('users')->insert([
            'id' => $id,
            'name' => 'Test User '.$id,
            'email' => 'timeline-user'.$id.'@example.test',
            'password' => 'password',
            'created_at' => '2026-03-28 11:00:00',
            'updated_at' => '2026-03-28 11:00:00',
        ]);
    }
}
