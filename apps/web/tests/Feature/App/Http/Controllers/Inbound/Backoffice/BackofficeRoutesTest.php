<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Inbound\Backoffice\ClickIndexController;
use App\Http\Controllers\Inbound\Backoffice\DashboardController;
use App\Http\Controllers\Inbound\Backoffice\LeadIndexController;
use App\Http\Controllers\Inbound\Backoffice\LeadStatusReportController;
use App\Http\Controllers\Inbound\Backoffice\LeadShowController;
use App\Http\Controllers\Inbound\Backoffice\ReportsIndexController;
use App\Http\Controllers\Inbound\Backoffice\StoreLeadNoteController;
use App\Http\Controllers\Inbound\Backoffice\TouchIndexController;
use App\Http\Controllers\Inbound\Backoffice\UpdateLeadStatusController;
use App\Http\Controllers\Inbound\Backoffice\VisitIndexController;
use Illuminate\Support\Facades\Route;
use Inbound\Application\Queries\Backoffice\ListClicks\ClicksListReadModel;
use Inbound\Application\Queries\Backoffice\ListClicks\ClicksListView;
use Inbound\Application\Queries\Backoffice\ListClicks\ListClicksQuery;
use Inbound\Application\Queries\Backoffice\ListTouches\ListTouchesQuery;
use Inbound\Application\Queries\Backoffice\ListTouches\TouchesListReadModel;
use Inbound\Application\Queries\Backoffice\ListTouches\TouchesListView;
use Inbound\Application\Queries\Backoffice\ListVisits\ListVisitsQuery;
use Inbound\Application\Queries\Backoffice\ListVisits\VisitsListReadModel;
use Inbound\Application\Queries\Backoffice\ListVisits\VisitsListView;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\GetLeadStatusReportQuery;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\LeadStatusReportReadModel;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\LeadStatusReportRowView;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\LeadStatusReportView;
use Mockery;
use Tests\TestCase;

final class BackofficeRoutesTest extends TestCase
{
    public function test_it_registers_named_routes_for_the_operational_backoffice_flow(): void
    {
        $dashboardRoute = Route::getRoutes()->getByName('admin.dashboard');
        $reportsIndexRoute = Route::getRoutes()->getByName('admin.reports.index');
        $leadStatusReportRoute = Route::getRoutes()->getByName('admin.reports.lead-status');
        $clicksIndexRoute = Route::getRoutes()->getByName('admin.clicks.index');
        $leadsIndexRoute = Route::getRoutes()->getByName('admin.leads.index');
        $leadShowRoute = Route::getRoutes()->getByName('admin.leads.show');
        $leadNoteStoreRoute = Route::getRoutes()->getByName('admin.leads.notes.store');
        $leadStatusUpdateRoute = Route::getRoutes()->getByName('admin.leads.status.update');
        $touchesIndexRoute = Route::getRoutes()->getByName('admin.touches.index');
        $visitsIndexRoute = Route::getRoutes()->getByName('admin.visits.index');

        $this->assertSame(DashboardController::class, $dashboardRoute?->getActionName());
        $this->assertSame(ReportsIndexController::class, $reportsIndexRoute?->getActionName());
        $this->assertSame(LeadStatusReportController::class, $leadStatusReportRoute?->getActionName());
        $this->assertSame(ClickIndexController::class, $clicksIndexRoute?->getActionName());
        $this->assertSame(LeadIndexController::class, $leadsIndexRoute?->getActionName());
        $this->assertSame(LeadShowController::class, $leadShowRoute?->getActionName());
        $this->assertSame(StoreLeadNoteController::class, $leadNoteStoreRoute?->getActionName());
        $this->assertSame(TouchIndexController::class, $touchesIndexRoute?->getActionName());
        $this->assertSame(UpdateLeadStatusController::class, $leadStatusUpdateRoute?->getActionName());
        $this->assertSame(VisitIndexController::class, $visitsIndexRoute?->getActionName());

        $this->assertSame(['GET', 'HEAD'], $dashboardRoute?->methods());
        $this->assertSame(['GET', 'HEAD'], $reportsIndexRoute?->methods());
        $this->assertSame(['GET', 'HEAD'], $leadStatusReportRoute?->methods());
        $this->assertSame(['GET', 'HEAD'], $clicksIndexRoute?->methods());
        $this->assertSame(['GET', 'HEAD'], $leadsIndexRoute?->methods());
        $this->assertSame(['GET', 'HEAD'], $leadShowRoute?->methods());
        $this->assertSame(['POST'], $leadNoteStoreRoute?->methods());
        $this->assertSame(['PATCH'], $leadStatusUpdateRoute?->methods());
        $this->assertSame(['GET', 'HEAD'], $touchesIndexRoute?->methods());
        $this->assertSame(['GET', 'HEAD'], $visitsIndexRoute?->methods());

        $this->assertSame(url('/admin'), route('admin.dashboard'));
        $this->assertSame(url('/admin/reports'), route('admin.reports.index'));
        $this->assertSame(url('/admin/reports/lead-status'), route('admin.reports.lead-status'));
        $this->assertSame(url('/admin/clicks'), route('admin.clicks.index'));
        $this->assertSame(url('/admin/leads'), route('admin.leads.index'));
        $this->assertSame(url('/admin/leads/lead-1'), route('admin.leads.show', ['leadId' => 'lead-1']));
        $this->assertSame(url('/admin/leads/lead-1/notes'), route('admin.leads.notes.store', ['leadId' => 'lead-1']));
        $this->assertSame(url('/admin/leads/lead-1/status'), route('admin.leads.status.update', ['leadId' => 'lead-1']));
        $this->assertSame(url('/admin/touches'), route('admin.touches.index'));
        $this->assertSame(url('/admin/visits'), route('admin.visits.index'));
    }

    public function test_it_keeps_the_first_reporting_route_available_via_a_dedicated_controller(): void
    {
        $readModel = Mockery::mock(LeadStatusReportReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::type(GetLeadStatusReportQuery::class))
            ->andReturn(new LeadStatusReportView(
                leadsCount: 0,
                rows: [
                    new LeadStatusReportRowView(
                        status: 'new',
                        statusLabel: 'Новий',
                        leadsCount: 0,
                        shareOfTotalRate: 0.0,
                    ),
                ],
            ));

        $this->app->instance(LeadStatusReportReadModel::class, $readModel);

        $this->get(route('admin.reports.lead-status'))
            ->assertOk()
            ->assertSeeText([
                'Пресет',
                'Період',
            ])
            ->assertSee('type="date"', false);
    }

    public function test_it_keeps_clicks_route_available_via_a_dedicated_controller(): void
    {
        $readModel = Mockery::mock(ClicksListReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::type(ListClicksQuery::class))
            ->andReturn(new ClicksListView(
                currentPage: 1,
                perPage: 20,
                total: 0,
                lastPage: 1,
                items: [],
            ));

        $this->app->instance(ClicksListReadModel::class, $readModel);

        $this->get(route('admin.clicks.index'))
            ->assertOk()
            ->assertSeeText([
                'Список кліків',
                'Кліків за поточними фільтрами не знайдено.',
            ]);
    }

    public function test_it_keeps_visits_route_available_via_a_dedicated_controller(): void
    {
        $readModel = Mockery::mock(VisitsListReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::type(ListVisitsQuery::class))
            ->andReturn(new VisitsListView(
                currentPage: 1,
                perPage: 20,
                total: 0,
                lastPage: 1,
                items: [],
            ));

        $this->app->instance(VisitsListReadModel::class, $readModel);

        $this->get(route('admin.visits.index'))
            ->assertOk()
            ->assertSeeText([
                'Список візитів',
                'Візитів за поточними фільтрами не знайдено.',
            ]);
    }

    public function test_it_keeps_touches_route_available_via_a_dedicated_controller(): void
    {
        $readModel = Mockery::mock(TouchesListReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::type(ListTouchesQuery::class))
            ->andReturn(new TouchesListView(
                currentPage: 1,
                perPage: 20,
                total: 0,
                lastPage: 1,
                items: [],
            ));

        $this->app->instance(TouchesListReadModel::class, $readModel);

        $this->get(route('admin.touches.index'))
            ->assertOk()
            ->assertSeeText([
                'Список дотиків',
                'Дотиків за поточними фільтрами не знайдено.',
            ]);
    }

    public function test_it_keeps_existing_status_update_delivery_behavior(): void
    {
        $this->patch(route('admin.leads.status.update', ['leadId' => 'lead-1']))
            ->assertRedirect(route('admin.leads.show', ['leadId' => 'lead-1']).'#lead-status-form');
    }
}
