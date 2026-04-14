<?php

use App\Http\Controllers\Auth\BackofficeSessionController;
use App\Http\Controllers\Inbound\Capture\CreateLeadController;
use App\Http\Controllers\Inbound\Capture\LandingController;
use App\Http\Controllers\Inbound\Capture\RegisterController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [BackofficeSessionController::class, 'create'])->name('login');
Route::post('/login', [BackofficeSessionController::class, 'store'])->name('login.store');
Route::post('/logout', [BackofficeSessionController::class, 'destroy'])->middleware('auth')->name('logout');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');

Route::get('/', LandingController::class)->name('landing');

Route::prefix('capture')
    ->name('capture.')
    ->group(function (): void {
        Route::post('/click', [RegisterController::class, 'click'])->name('click');
        Route::post('/touch', [RegisterController::class, 'touch'])->name('touch');
        Route::post('/leads/form', [CreateLeadController::class, 'form'])->name('leads.form');
        Route::post('/leads/phone-click', [CreateLeadController::class, 'phoneClick'])->name('leads.phone-click');
    });
