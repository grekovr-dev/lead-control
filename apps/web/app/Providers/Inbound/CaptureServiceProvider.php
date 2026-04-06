<?php

namespace App\Providers\Inbound;

use App\Http\Cookies\Inbound\Capture\VisitorIdCookieConfig;
use DateInterval;
use Illuminate\Support\ServiceProvider;
use Inbound\Application\Actions\Capture\ResolveCurrentVisit\VisitSessionRule;
use Inbound\Application\Transactions\TransactionManager;
use Inbound\Domain\Click\ClickRepository;
use Inbound\Domain\Lead\LeadRepository;
use Inbound\Domain\Touch\TouchRepository;
use Inbound\Domain\Visit\VisitRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentClickRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentTouchRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentVisitRepository;
use Inbound\Infrastructure\Persistence\LaravelTransactionManager;

class CaptureServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(VisitorIdCookieConfig::class, function ($app): VisitorIdCookieConfig {
            return new VisitorIdCookieConfig(
                lifetimeDays: (int) $app['config']->get('inbound.capture.visitor_cookie_lifetime_days', 30),
                secure: (bool) $app['config']->get('inbound.capture.cookie_secure', true),
            );
        });

        $this->app->bind(\App\Http\Cookies\Inbound\Capture\AttributionCookieStore::class, function ($app) {
            return new \App\Http\Cookies\Inbound\Capture\AttributionCookieStore(
                secure: (bool) $app['config']->get('inbound.capture.cookie_secure', true),
            );
        });

        $this->app->bind(VisitSessionRule::class, function ($app): VisitSessionRule {
            return new VisitSessionRule(
                new DateInterval((string) $app['config']->get('inbound.capture.visit_session_lifetime', 'PT30M')),
            );
        });

        $this->app->bind(ClickRepository::class, EloquentClickRepository::class);
        $this->app->bind(VisitRepository::class, EloquentVisitRepository::class);
        $this->app->bind(TouchRepository::class, EloquentTouchRepository::class);
        $this->app->bind(LeadRepository::class, EloquentLeadRepository::class);
        $this->app->bind(TransactionManager::class, LaravelTransactionManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
