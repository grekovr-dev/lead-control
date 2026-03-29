<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Backoffice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\DashboardOverviewView;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\TouchModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use Tests\TestCase;

final class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_current_backoffice_shell_in_ukrainian(): void
    {
        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewIs('admin.dashboard.index');
        $response->assertSeeText([
            'Lead Control',
            'Бекофіс',
            'Огляд',
            'Операційна робота з лідами',
            'Кліки',
            'Візити',
            'Дотики',
            'Ліди',
            'Новий',
            'Статуси лідів',
            'Типи дотиків',
            'Походження лідів',
            'Останні ліди',
        ]);
        $response->assertSee('<html lang="uk">', false);
        $response->assertSee('<title>Огляд • Lead Control</title>', false);
        $response->assertSee('x-data="adminShell()"', false);
        $response->assertSee('aria-label="Відкрити бокову навігацію"', false);
        $response->assertSee('aria-label="Закрити бокову навігацію"', false);
        $response->assertSee('title="Ліди"', false);
        $response->assertSee('Незабаром', false);
    }

    public function test_it_renders_dashboard_metrics_from_the_backoffice_overview_read_model(): void
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

        VisitModel::query()->create([
            'id' => 'visit-1',
            'visitor_id' => 'visitor-1',
            'started_at' => '2026-03-28 11:00:00',
            'last_touched_at' => '2026-03-28 11:10:00',
        ]);

        TouchModel::query()->create([
            'id' => 'touch-1',
            'visit_id' => 'visit-1',
            'visitor_id' => 'visitor-1',
            'type' => 'lead_form_click',
            'occurred_at' => '2026-03-28 11:07:00',
        ]);

        LeadModel::query()->create([
            'id' => 'lead-1',
            'visitor_id' => 'visitor-1',
            'visit_id' => 'visit-1',
            'name' => 'John Doe',
            'phone' => '+380501112233',
            'status' => 'new',
            'origin' => 'form',
            'attribution_source' => 'google',
            'attribution_medium' => 'cpc',
            'created_at' => '2026-03-28 11:30:00',
        ]);

        $response = $this->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('overview', function ($overview): bool {
            return $overview instanceof DashboardOverviewView
                && $overview->clicksCount === 2
                && $overview->visitsCount === 1
                && $overview->touchesCount === 1
                && $overview->leadsCount === 1
                && $overview->clicksToLeadsConversionRate === 50.0
                && $overview->visitsToLeadsConversionRate === 100.0
                && count($overview->recentLeads) === 1;
        });
        $response->assertSeeText([
            'Кліки',
            'Візити',
            'Дотики',
            'Ліди',
            'Кліки → ліди',
            'Візити → ліди',
            'Статуси лідів',
            'Типи дотиків',
            'Походження лідів',
            'Останні ліди',
            'John Doe',
            'Новий',
            'Форма',
            'Клік по формі',
        ]);
    }
}
