<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\GetDashboardOverviewQuery;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentDashboardOverviewReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\TouchModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use Tests\TestCase;

final class EloquentDashboardOverviewReadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_dashboard_counts_and_conversion_rates(): void
    {
        ClickModel::query()->create([
            'id' => 'click-1',
            'visitor_id' => 'visitor-1',
            'landing_url' => 'https://example.com/landing-1',
            'referrer' => null,
            'occurred_at' => '2026-03-28 11:00:00',
        ]);

        ClickModel::query()->create([
            'id' => 'click-2',
            'visitor_id' => 'visitor-2',
            'landing_url' => 'https://example.com/landing-2',
            'referrer' => null,
            'occurred_at' => '2026-03-28 11:05:00',
        ]);

        ClickModel::query()->create([
            'id' => 'click-3',
            'visitor_id' => 'visitor-3',
            'landing_url' => 'https://example.com/landing-3',
            'referrer' => null,
            'occurred_at' => '2026-03-28 11:10:00',
        ]);

        ClickModel::query()->create([
            'id' => 'click-4',
            'visitor_id' => 'visitor-4',
            'landing_url' => 'https://example.com/landing-4',
            'referrer' => null,
            'occurred_at' => '2026-03-28 11:15:00',
        ]);

        VisitModel::query()->create([
            'id' => 'visit-1',
            'visitor_id' => 'visitor-1',
            'started_at' => '2026-03-28 11:00:00',
            'last_touched_at' => '2026-03-28 11:10:00',
        ]);

        VisitModel::query()->create([
            'id' => 'visit-2',
            'visitor_id' => 'visitor-2',
            'started_at' => '2026-03-28 11:05:00',
            'last_touched_at' => '2026-03-28 11:20:00',
        ]);

        TouchModel::query()->create([
            'id' => 'touch-1',
            'visit_id' => 'visit-1',
            'visitor_id' => 'visitor-1',
            'type' => 'lead_form_click',
            'occurred_at' => '2026-03-28 11:07:00',
        ]);

        LeadModel::query()->create([
            'id' => '123e4567-e89b-12d3-a456-426614174000',
            'visitor_id' => 'visitor-1',
            'visit_id' => 'visit-1',
            'name' => 'John Doe',
            'phone' => '+380501112233',
            'status' => 'new',
            'origin' => 'form',
            'created_at' => '2026-03-28 11:30:00',
        ]);

        $readModel = new EloquentDashboardOverviewReadModel();

        $overview = $readModel(new GetDashboardOverviewQuery());

        $this->assertSame(4, $overview->clicksCount);
        $this->assertSame(2, $overview->visitsCount);
        $this->assertSame(1, $overview->touchesCount);
        $this->assertSame(1, $overview->leadsCount);
        $this->assertSame(25.0, $overview->clicksToLeadsConversionRate);
        $this->assertSame(50.0, $overview->visitsToLeadsConversionRate);
        $this->assertCount(1, $overview->recentLeads);
        $this->assertSame('123e4567-e89b-12d3-a456-426614174000', $overview->recentLeads[0]->leadId);
        $this->assertSame('123e4567-e89b', $overview->recentLeads[0]->shortLeadId);
        $this->assertSame('Форма', $overview->recentLeads[0]->originLabel);
    }

    public function test_it_returns_zero_conversion_rates_when_source_counts_are_missing(): void
    {
        $readModel = new EloquentDashboardOverviewReadModel();

        $overview = $readModel(new GetDashboardOverviewQuery());

        $this->assertSame(0, $overview->clicksCount);
        $this->assertSame(0, $overview->visitsCount);
        $this->assertSame(0, $overview->leadsCount);
        $this->assertSame(0.0, $overview->clicksToLeadsConversionRate);
        $this->assertSame(0.0, $overview->visitsToLeadsConversionRate);
    }
}
