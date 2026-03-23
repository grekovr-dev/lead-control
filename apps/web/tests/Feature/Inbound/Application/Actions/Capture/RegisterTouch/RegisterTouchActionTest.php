<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Application\Actions\Capture\RegisterTouch;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Actions\Capture\RegisterTouch\RegisterTouchAction;
use Inbound\Application\Actions\Capture\RegisterTouch\RegisterTouchCommand;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Touch\Touch;
use Inbound\Domain\Touch\TouchId;
use Inbound\Domain\Touch\TouchType;
use Inbound\Domain\Visit\VisitId;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use Tests\TestCase;

final class RegisterTouchActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_touch_and_new_visit_when_last_visit_is_missing(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-23 11:10:00');
        $command = new RegisterTouchCommand(
            new TouchId('touch-new'),
            new VisitId('visit-new'),
            new VisitorId('550e8400-e29b-41d4-a716-446655440000'),
            TouchType::FormSubmit,
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            $occurredAt,
        );

        $action = $this->app->make(RegisterTouchAction::class);
        $touch = $action($command);

        $this->assertInstanceOf(Touch::class, $touch);
        $this->assertSame('touch-new', $touch->id()->value());
        $this->assertSame('visit-new', $touch->visitId()->value());
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $touch->visitorId()->value());
        $this->assertSame(TouchType::FormSubmit, $touch->type());

        $this->assertDatabaseCount('touches', 1);
        $this->assertDatabaseHas('touches', [
            'id' => 'touch-new',
            'visit_id' => 'visit-new',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'type' => 'form_submit',
            'occurred_at' => '2026-03-23 11:10:00',
        ]);

        $this->assertDatabaseCount('visits', 1);
        $this->assertDatabaseHas('visits', [
            'id' => 'visit-new',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'started_at' => '2026-03-23 11:10:00',
            'last_touched_at' => '2026-03-23 11:10:00',
            'first_attribution_source' => 'google',
            'last_attribution_source' => 'google',
        ]);
    }

    public function test_it_creates_touch_and_reuses_existing_visit_when_session_continues(): void
    {
        VisitModel::query()->create([
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'started_at' => '2026-03-23 11:00:00',
            'last_touched_at' => '2026-03-23 11:05:00',
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'old-medium',
        ]);

        $occurredAt = new DateTimeImmutable('2026-03-23 11:10:00');
        $command = new RegisterTouchCommand(
            new TouchId('touch-existing'),
            new VisitId('visit-ignored'),
            new VisitorId('550e8400-e29b-41d4-a716-446655440000'),
            TouchType::MessengerClick,
            new Attribution('google', 'remarketing', null, null, null, null, null, null),
            $occurredAt,
        );

        $action = $this->app->make(RegisterTouchAction::class);
        $touch = $action($command);

        $this->assertInstanceOf(Touch::class, $touch);
        $this->assertSame('touch-existing', $touch->id()->value());
        $this->assertSame('visit-existing', $touch->visitId()->value());
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $touch->visitorId()->value());
        $this->assertSame(TouchType::MessengerClick, $touch->type());

        $this->assertDatabaseCount('touches', 1);
        $this->assertDatabaseHas('touches', [
            'id' => 'touch-existing',
            'visit_id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'type' => 'messenger_click',
            'occurred_at' => '2026-03-23 11:10:00',
        ]);

        $this->assertDatabaseCount('visits', 1);
        $this->assertDatabaseHas('visits', [
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'started_at' => '2026-03-23 11:00:00',
            'last_touched_at' => '2026-03-23 11:10:00',
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'remarketing',
        ]);
    }
}
