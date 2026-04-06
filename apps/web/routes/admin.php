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
        Route::middleware('backoffice.permission:dashboard.view')
            ->get('/', DashboardController::class)
            ->name('dashboard');

        Route::prefix('reports')
            ->name('reports.')
            ->middleware('backoffice.permission:reports.view')
            ->group(function (): void {
                Route::get('/', ReportsIndexController::class)->name('index');
                Route::get('/lead-status', LeadStatusReportController::class)->name('lead-status');
                Route::get('/origin-funnel', OriginFunnelReportController::class)->name('origin-funnel');
                Route::get('/visitor-acquisition-funnel', VisitorAcquisitionFunnelReportController::class)->name('visitor-acquisition-funnel');
                Route::get('/visit-attribution-funnel', VisitAttributionFunnelReportController::class)->name('visit-attribution-funnel');
                Route::get('/funnel-trends', FunnelTrendsController::class)->name('funnel-trends');
            });

        Route::middleware('backoffice.permission:clicks.view')
            ->get('/clicks', ClickIndexController::class)
            ->name('clicks.index');

        Route::prefix('leads')
            ->name('leads.')
            ->middleware('backoffice.permission:leads.view')
            ->group(function (): void {
                Route::get('/', LeadIndexController::class)->name('index');
                Route::get('/{leadId}', LeadShowController::class)->name('show');
                Route::post('/{leadId}/notes', StoreLeadNoteController::class)
                    ->middleware('backoffice.permission:leads.note.create')
                    ->name('notes.store');
                Route::patch('/{leadId}/status', UpdateLeadStatusController::class)
                    ->middleware('backoffice.permission:leads.status.update')
                    ->name('status.update');
            });

        Route::middleware('backoffice.permission:touches.view')
            ->get('/touches', TouchIndexController::class)
            ->name('touches.index');

        Route::middleware('backoffice.permission:visits.view')
            ->get('/visits', VisitIndexController::class)
            ->name('visits.index');
    });
