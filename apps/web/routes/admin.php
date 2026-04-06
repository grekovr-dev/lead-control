<?php

use App\Http\Controllers\Inbound\Backoffice\ClickIndexController;
use App\Http\Controllers\Inbound\Backoffice\DashboardController;
use App\Http\Controllers\Inbound\Backoffice\FunnelTrendsController;
use App\Http\Controllers\Inbound\Backoffice\LeadIndexController;
use App\Http\Controllers\Inbound\Backoffice\LeadShowController;
use App\Http\Controllers\Inbound\Backoffice\LeadStatusReportController;
use App\Http\Controllers\Inbound\Backoffice\OriginFunnelReportController;
use App\Http\Controllers\Inbound\Backoffice\ReportsIndexController;
use App\Http\Controllers\Inbound\Backoffice\StoreLeadNoteController;
use App\Http\Controllers\Inbound\Backoffice\TouchIndexController;
use App\Http\Controllers\Inbound\Backoffice\UpdateLeadStatusController;
use App\Http\Controllers\Inbound\Backoffice\VisitAttributionFunnelReportController;
use App\Http\Controllers\Inbound\Backoffice\VisitIndexController;
use App\Http\Controllers\Inbound\Backoffice\VisitorAcquisitionFunnelReportController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/reports', ReportsIndexController::class)->name('reports.index');
        Route::get('/reports/lead-status', LeadStatusReportController::class)->name('reports.lead-status');
        Route::get('/reports/origin-funnel', OriginFunnelReportController::class)->name('reports.origin-funnel');
        Route::get('/reports/visitor-acquisition-funnel', VisitorAcquisitionFunnelReportController::class)->name('reports.visitor-acquisition-funnel');
        Route::get('/reports/visit-attribution-funnel', VisitAttributionFunnelReportController::class)->name('reports.visit-attribution-funnel');
        Route::get('/reports/funnel-trends', FunnelTrendsController::class)->name('reports.funnel-trends');
        Route::get('/clicks', ClickIndexController::class)->name('clicks.index');
        Route::get('/leads', LeadIndexController::class)->name('leads.index');
        Route::get('/leads/{leadId}', LeadShowController::class)->name('leads.show');
        Route::post('/leads/{leadId}/notes', StoreLeadNoteController::class)->name('leads.notes.store');
        Route::patch('/leads/{leadId}/status', UpdateLeadStatusController::class)->name('leads.status.update');
        Route::get('/touches', TouchIndexController::class)->name('touches.index');
        Route::get('/visits', VisitIndexController::class)->name('visits.index');
    });
