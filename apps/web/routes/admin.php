<?php

use App\Http\Controllers\Inbound\Backoffice\DashboardController;
use App\Http\Controllers\Inbound\Backoffice\LeadIndexController;
use App\Http\Controllers\Inbound\Backoffice\LeadShowController;
use App\Http\Controllers\Inbound\Backoffice\StoreLeadNoteController;
use App\Http\Controllers\Inbound\Backoffice\UpdateLeadStatusController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/leads', LeadIndexController::class)->name('leads.index');
        Route::get('/leads/{leadId}', LeadShowController::class)->name('leads.show');
        Route::post('/leads/{leadId}/notes', StoreLeadNoteController::class)->name('leads.notes.store');
        Route::patch('/leads/{leadId}/status', UpdateLeadStatusController::class)->name('leads.status.update');
    });
