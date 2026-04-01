<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Application\Actions\Capture\RegisterTouch;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\CurrentVisitNotFoundException;
use Inbound\Application\Actions\Capture\RegisterTouch\RegisterTouchAction;
use Inbound\Application\Actions\Capture\RegisterTouch\RegisterTouchCommand;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Touch\Touch;
use Inbound\Domain\Touch\TouchId;
use Inbound\Domain\Touch\TouchType;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use Tests\TestCase;

final class RegisterTouchActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_throws_when_current_visit_is_missing(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-23 11:10:00');
        $command = new RegisterTouchCommand(
            new TouchId('touch-new'),
            new VisitorId('550e8400-e29b-41d4-a716-446655440000'),
            TouchType::LeadFormClick,
            $occurredAt,
        );

        $action = $this->app->make(RegisterTouchAction::class);

        $this->expectException(CurrentVisitNotFoundException::class);
        $this->expectExceptionMessage('Cannot continue current visit without an existing visit.');

        $action($command);

        $this->assertDatabaseCount('touches', 0);
        $this->assertDatabaseCount('visits', 0);
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
            new VisitorId('550e8400-e29b-41d4-a716-446655440000'),
            TouchType::MessengerClick,
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
            'last_attribution_medium' => 'old-medium',
        ]);
    }
}
