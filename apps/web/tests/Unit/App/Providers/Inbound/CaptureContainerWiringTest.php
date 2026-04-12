<?php

declare(strict_types=1);

namespace Tests\Unit\App\Providers\Inbound;

use App\Http\Cookies\Inbound\Capture\AttributionCookieStore;
use App\Http\Cookies\Inbound\Capture\VisitorIdCookieConfig;
use App\Jobs\Inbound\Notifications\SendManagerLeadCreatedTelegramJob;
use DateTimeImmutable;
use Illuminate\Support\Facades\Queue;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\ContinueCurrentVisitAction;
use Inbound\Application\Actions\Capture\CreateLeadFromForm\CreateLeadFromFormAction;
use Inbound\Application\Actions\Capture\RegisterClick\RegisterClickAction;
use Inbound\Application\Actions\Capture\RegisterTouch\RegisterTouchAction;
use Inbound\Application\Actions\Capture\ResolveCurrentVisit\ResolveCurrentVisitAction;
use Inbound\Application\Actions\Capture\ResolveCurrentVisit\VisitSessionRule;
use Inbound\Application\Events\EventBus;
use Inbound\Application\Identifiers\UuidGenerator;
use Inbound\Application\Notifications\Telegram\TelegramClient;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\ManagerLeadNotificationReadModel;
use Inbound\Application\Reactions\Lead\ManagerLeadNotificationScheduler;
use Inbound\Application\Transactions\TransactionManager;
use Inbound\Domain\Click\ClickRepository;
use Inbound\Domain\Lead\Events\LeadCreated;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Lead\LeadRepository;
use Inbound\Domain\Revisit\RevisitRepository;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Touch\TouchRepository;
use Inbound\Domain\Visit\Visit;
use Inbound\Domain\Visit\VisitId;
use Inbound\Domain\Visit\VisitRepository;
use Inbound\Infrastructure\Events\LaravelEventBus;
use Inbound\Infrastructure\Identifiers\LaravelUuidGenerator;
use Inbound\Infrastructure\Notifications\LaravelManagerLeadNotificationScheduler;
use Inbound\Infrastructure\Notifications\Telegram\LaravelHttpTelegramClient;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentClickRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentRevisitRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentTouchRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentVisitRepository;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentManagerLeadNotificationReadModel;
use Inbound\Infrastructure\Persistence\LaravelTransactionManager;
use Tests\TestCase;

final class CaptureContainerWiringTest extends TestCase
{
    public function test_it_binds_capture_repository_interfaces_to_eloquent_implementations(): void
    {
        $this->assertInstanceOf(EloquentClickRepository::class, $this->app->make(ClickRepository::class));
        $this->assertInstanceOf(EloquentVisitRepository::class, $this->app->make(VisitRepository::class));
        $this->assertInstanceOf(EloquentRevisitRepository::class, $this->app->make(RevisitRepository::class));
        $this->assertInstanceOf(EloquentTouchRepository::class, $this->app->make(TouchRepository::class));
        $this->assertInstanceOf(EloquentLeadRepository::class, $this->app->make(LeadRepository::class));
        $this->assertInstanceOf(LaravelUuidGenerator::class, $this->app->make(UuidGenerator::class));
        $this->assertInstanceOf(LaravelEventBus::class, $this->app->make(EventBus::class));
        $this->assertInstanceOf(LaravelManagerLeadNotificationScheduler::class, $this->app->make(ManagerLeadNotificationScheduler::class));
        $this->assertInstanceOf(EloquentManagerLeadNotificationReadModel::class, $this->app->make(ManagerLeadNotificationReadModel::class));
        $this->assertInstanceOf(LaravelHttpTelegramClient::class, $this->app->make(TelegramClient::class));
        $this->assertInstanceOf(LaravelTransactionManager::class, $this->app->make(TransactionManager::class));
    }

    public function test_it_resolves_capture_actions_via_the_container(): void
    {
        $this->assertInstanceOf(RegisterClickAction::class, $this->app->make(RegisterClickAction::class));
        $this->assertInstanceOf(RegisterTouchAction::class, $this->app->make(RegisterTouchAction::class));
        $this->assertInstanceOf(CreateLeadFromFormAction::class, $this->app->make(CreateLeadFromFormAction::class));
        $this->assertInstanceOf(ContinueCurrentVisitAction::class, $this->app->make(ContinueCurrentVisitAction::class));
        $this->assertInstanceOf(ResolveCurrentVisitAction::class, $this->app->make(ResolveCurrentVisitAction::class));
    }

    public function test_it_binds_visit_session_rule_using_laravel_config(): void
    {
        $this->app['config']->set('inbound.capture.visit_session_lifetime', 'PT45M');

        $rule = $this->app->make(VisitSessionRule::class);
        $visit = new Visit(
            new VisitId('visit-123'),
            new VisitorId('visitor-123'),
            Attribution::empty(),
            Attribution::empty(),
            new DateTimeImmutable('2026-03-23 10:00:00'),
            new DateTimeImmutable('2026-03-23 10:00:00'),
        );

        $this->assertTrue($rule->continues($visit, new DateTimeImmutable('2026-03-23 10:45:00')));
        $this->assertFalse($rule->continues($visit, new DateTimeImmutable('2026-03-23 10:46:00')));
    }

    public function test_it_binds_visitor_id_cookie_config_using_laravel_config(): void
    {
        $this->app['config']->set('inbound.capture.visitor_cookie_lifetime_days', 45);
        $this->app['config']->set('inbound.capture.cookie_secure', false);

        $config = $this->app->make(VisitorIdCookieConfig::class);

        $this->assertSame('inbound_visitor_id', $config->cookieName());
        $this->assertSame(45, $config->lifetimeDays());
        $this->assertFalse($config->secure());
    }

    public function test_it_binds_attribution_cookie_store_using_laravel_config(): void
    {
        $this->app['config']->set('inbound.capture.cookie_secure', false);

        $store = $this->app->make(AttributionCookieStore::class);
        $cookie = $store->make(Attribution::empty());

        $this->assertFalse($cookie->isSecure());
    }

    public function test_it_routes_lead_created_events_from_the_event_bus_to_the_manager_notification_job(): void
    {
        Queue::fake();

        $eventBus = $this->app->make(EventBus::class);

        $eventBus->publish(
            new LeadCreated(
                new LeadId('lead-123'),
                new DateTimeImmutable('2026-04-09 10:00:00'),
            ),
        );

        Queue::assertPushed(SendManagerLeadCreatedTelegramJob::class, function (SendManagerLeadCreatedTelegramJob $job): bool {
            return $job->leadId === 'lead-123';
        });
    }
}
