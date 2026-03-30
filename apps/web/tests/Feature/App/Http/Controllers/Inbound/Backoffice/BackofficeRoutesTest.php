<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Backoffice;

use App\Http\Controllers\Inbound\Backoffice\DashboardController;
use App\Http\Controllers\Inbound\Backoffice\LeadIndexController;
use App\Http\Controllers\Inbound\Backoffice\LeadShowController;
use App\Http\Controllers\Inbound\Backoffice\StoreLeadNoteController;
use App\Http\Controllers\Inbound\Backoffice\UpdateLeadStatusController;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class BackofficeRoutesTest extends TestCase
{
    public function test_it_registers_named_routes_for_the_operational_backoffice_flow(): void
    {
        $dashboardRoute = Route::getRoutes()->getByName('admin.dashboard');
        $leadsIndexRoute = Route::getRoutes()->getByName('admin.leads.index');
        $leadShowRoute = Route::getRoutes()->getByName('admin.leads.show');
        $leadNoteStoreRoute = Route::getRoutes()->getByName('admin.leads.notes.store');
        $leadStatusUpdateRoute = Route::getRoutes()->getByName('admin.leads.status.update');

        $this->assertSame(DashboardController::class, $dashboardRoute?->getActionName());
        $this->assertSame(LeadIndexController::class, $leadsIndexRoute?->getActionName());
        $this->assertSame(LeadShowController::class, $leadShowRoute?->getActionName());
        $this->assertSame(StoreLeadNoteController::class, $leadNoteStoreRoute?->getActionName());
        $this->assertSame(UpdateLeadStatusController::class, $leadStatusUpdateRoute?->getActionName());

        $this->assertSame(['GET', 'HEAD'], $dashboardRoute?->methods());
        $this->assertSame(['GET', 'HEAD'], $leadsIndexRoute?->methods());
        $this->assertSame(['GET', 'HEAD'], $leadShowRoute?->methods());
        $this->assertSame(['POST'], $leadNoteStoreRoute?->methods());
        $this->assertSame(['PATCH'], $leadStatusUpdateRoute?->methods());

        $this->assertSame(url('/admin'), route('admin.dashboard'));
        $this->assertSame(url('/admin/leads'), route('admin.leads.index'));
        $this->assertSame(url('/admin/leads/lead-1'), route('admin.leads.show', ['leadId' => 'lead-1']));
        $this->assertSame(url('/admin/leads/lead-1/notes'), route('admin.leads.notes.store', ['leadId' => 'lead-1']));
        $this->assertSame(url('/admin/leads/lead-1/status'), route('admin.leads.status.update', ['leadId' => 'lead-1']));
    }

    public function test_it_keeps_only_routes_for_unfinished_backoffice_flows_as_placeholders(): void
    {
        $this->patch(route('admin.leads.status.update', ['leadId' => 'lead-1']))
            ->assertRedirect(route('admin.leads.show', ['leadId' => 'lead-1']).'#lead-status-form');
    }
}
