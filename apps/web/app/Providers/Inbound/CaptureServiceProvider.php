<?php

namespace App\Providers\Inbound;

use Illuminate\Support\ServiceProvider;
use Inbound\Domain\Click\ClickRepository;
use Inbound\Domain\Lead\LeadRepository;
use Inbound\Domain\Touch\TouchRepository;
use Inbound\Domain\Visit\VisitRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentClickRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentTouchRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentVisitRepository;

class CaptureServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ClickRepository::class, EloquentClickRepository::class);
        $this->app->bind(VisitRepository::class, EloquentVisitRepository::class);
        $this->app->bind(TouchRepository::class, EloquentTouchRepository::class);
        $this->app->bind(LeadRepository::class, EloquentLeadRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
