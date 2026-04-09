<?php

namespace App\Providers\Inbound;

use App\Http\Cookies\Inbound\Capture\AttributionCookieStore;
use App\Http\Cookies\Inbound\Capture\VisitorIdCookieConfig;
use DateInterval;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Inbound\Application\Actions\Capture\ResolveCurrentVisit\VisitSessionRule;
use Inbound\Application\Events\EventBus;
use Inbound\Application\Notifications\Telegram\TelegramClient;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\ManagerLeadNotificationReadModel;
use Inbound\Application\Reactions\Lead\ManagerLeadNotificationScheduler;
use Inbound\Application\Reactions\Lead\NotifyManagerAboutNewLead;
use Inbound\Application\Transactions\TransactionManager;
use Inbound\Domain\Click\ClickRepository;
use Inbound\Domain\Lead\Events\LeadCreated;
use Inbound\Domain\Lead\LeadRepository;
use Inbound\Domain\Touch\TouchRepository;
use Inbound\Domain\Visit\VisitRepository;
use Inbound\Infrastructure\Events\LaravelEventBus;
use Inbound\Infrastructure\Notifications\LaravelManagerLeadNotificationScheduler;
use Inbound\Infrastructure\Notifications\Telegram\LaravelHttpTelegramClient;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentClickRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentTouchRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentVisitRepository;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentManagerLeadNotificationReadModel;
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

        $this->app->bind(AttributionCookieStore::class, function ($app) {
            return new AttributionCookieStore(
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
        $this->app->bind(EventBus::class, LaravelEventBus::class);
        $this->app->bind(ManagerLeadNotificationScheduler::class, LaravelManagerLeadNotificationScheduler::class);
        $this->app->bind(ManagerLeadNotificationReadModel::class, EloquentManagerLeadNotificationReadModel::class);
        $this->app->bind(TelegramClient::class, function ($app): TelegramClient {
            return new LaravelHttpTelegramClient(
                botToken: (string) $app['config']->get('services.telegram.bot_token'),
                baseUrl: (string) $app['config']->get('services.telegram.base_url', 'https://api.telegram.org'),
                timeoutSeconds: (int) $app['config']->get('services.telegram.timeout_seconds', 10),
            );
        });
        $this->app->bind(TransactionManager::class, LaravelTransactionManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(Dispatcher $events): void
    {
        $events->listen(LeadCreated::class, function (LeadCreated $event): void {
            ($this->app->make(NotifyManagerAboutNewLead::class))($event);
        });
    }
}
