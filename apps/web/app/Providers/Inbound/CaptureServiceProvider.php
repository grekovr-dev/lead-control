<?php

namespace App\Providers\Inbound;

use DateInterval;
use Illuminate\Support\ServiceProvider;
use Inbound\Application\Actions\Capture\ResolveVisitForCapture\VisitSessionRule;
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
        $this->app->bind(VisitSessionRule::class, function ($app): VisitSessionRule {
            return new VisitSessionRule(
                new DateInterval((string) $app['config']->get('inbound.capture.visit_session_lifetime', 'PT30M')),
            );
        });

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
