<?php

declare(strict_types=1);

namespace Tests\Unit\App\Providers\Inbound;

use DateTimeImmutable;
use Inbound\Application\Actions\Capture\CreateLeadFromForm\CreateLeadFromFormAction;
use Inbound\Application\Actions\Capture\CreateLeadFromPhoneClick\CreateLeadFromPhoneClickAction;
use Inbound\Application\Actions\Capture\RegisterClick\RegisterClickAction;
use Inbound\Application\Actions\Capture\RegisterTouch\RegisterTouchAction;
use Inbound\Application\Actions\Capture\ResolveVisitForCapture\ResolveVisitForCaptureAction;
use Inbound\Application\Actions\Capture\ResolveVisitForCapture\VisitSessionRule;
use Inbound\Domain\Click\ClickRepository;
use Inbound\Domain\Lead\LeadRepository;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Touch\TouchRepository;
use Inbound\Domain\Visit\Visit;
use Inbound\Domain\Visit\VisitId;
use Inbound\Domain\Visit\VisitRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentClickRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentTouchRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentVisitRepository;
use Tests\TestCase;

final class CaptureContainerWiringTest extends TestCase
{
    public function test_it_binds_capture_repository_interfaces_to_eloquent_implementations(): void
    {
        $this->assertInstanceOf(EloquentClickRepository::class, $this->app->make(ClickRepository::class));
        $this->assertInstanceOf(EloquentVisitRepository::class, $this->app->make(VisitRepository::class));
        $this->assertInstanceOf(EloquentTouchRepository::class, $this->app->make(TouchRepository::class));
        $this->assertInstanceOf(EloquentLeadRepository::class, $this->app->make(LeadRepository::class));
    }

    public function test_it_resolves_capture_actions_via_the_container(): void
    {
        $this->assertInstanceOf(RegisterClickAction::class, $this->app->make(RegisterClickAction::class));
        $this->assertInstanceOf(RegisterTouchAction::class, $this->app->make(RegisterTouchAction::class));
        $this->assertInstanceOf(CreateLeadFromFormAction::class, $this->app->make(CreateLeadFromFormAction::class));
        $this->assertInstanceOf(CreateLeadFromPhoneClickAction::class, $this->app->make(CreateLeadFromPhoneClickAction::class));
        $this->assertInstanceOf(ResolveVisitForCaptureAction::class, $this->app->make(ResolveVisitForCaptureAction::class));
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
}
