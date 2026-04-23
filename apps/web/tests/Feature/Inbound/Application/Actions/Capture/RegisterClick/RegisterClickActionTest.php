<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Application\Actions\Capture\RegisterClick;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Actions\Capture\RegisterClick\RegisterClickAction;
use Inbound\Application\Actions\Capture\RegisterClick\RegisterClickCommand;
use Inbound\Application\Actions\Capture\RegisterClick\RegisterClickResult;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use Tests\TestCase;

final class RegisterClickActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_click_and_new_visit_when_last_visit_is_missing(): void
    {
        $occurredAt = new DateTimeImmutable('2026-03-23 10:10:00');
        $command = new RegisterClickCommand(
            new VisitorId('550e8400-e29b-41d4-a716-446655440000'),
            new Attribution('google', 'cpc', null, null, null, null, null, null, ' https://example.com/catalog?utm_source=google '),
            'https://example.com/stretch-ceiling',
            $occurredAt,
        );

        $action = $this->app->make(RegisterClickAction::class);
        $result = $action($command);

        $this->assertInstanceOf(RegisterClickResult::class, $result);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $result->visitorId);
        $this->assertSame(RegisterClickResult::TYPE_CLICK, $result->resultType);
        $this->assertNotSame('', $result->visitId);
        $this->assertNotSame('', $result->resultId);

        $visitId = $result->visitId;

        $this->assertDatabaseCount('clicks', 1);
        $this->assertDatabaseHas('clicks', [
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'visit_id' => $visitId,
            'landing_url' => 'https://example.com/stretch-ceiling',
            'attribution_referrer' => 'https://example.com/catalog?utm_source=google',
            'attribution_source' => 'google',
            'attribution_medium' => 'cpc',
        ]);

        $this->assertDatabaseCount('visits', 1);
        $this->assertDatabaseHas('visits', [
            'id' => $visitId,
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'landing_url' => 'https://example.com/stretch-ceiling',
            'started_at' => '2026-03-23 10:10:00',
            'last_touched_at' => '2026-03-23 10:10:00',
            'first_attribution_source' => 'google',
            'first_attribution_referrer' => 'https://example.com/catalog?utm_source=google',
            'last_attribution_source' => 'google',
            'last_attribution_referrer' => 'https://example.com/catalog?utm_source=google',
        ]);
    }

    public function test_it_creates_click_and_reuses_existing_visit_when_session_continues(): void
    {
        VisitModel::query()->create([
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'started_at' => '2026-03-23 10:00:00',
            'last_touched_at' => '2026-03-23 10:05:00',
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'old-medium',
        ]);

        $occurredAt = new DateTimeImmutable('2026-03-23 10:10:00');
        $command = new RegisterClickCommand(
            new VisitorId('550e8400-e29b-41d4-a716-446655440000'),
            new Attribution('google', 'remarketing', null, null, null, null, null, null),
            'https://example.com/stretch-ceiling',
            $occurredAt,
        );

        $action = $this->app->make(RegisterClickAction::class);
        $result = $action($command);

        $this->assertInstanceOf(RegisterClickResult::class, $result);
        $this->assertSame('visit-existing', $result->visitId);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $result->visitorId);
        $this->assertSame(RegisterClickResult::TYPE_CLICK, $result->resultType);
        $this->assertNotSame('', $result->resultId);

        $this->assertDatabaseCount('clicks', 1);
        $this->assertDatabaseHas('clicks', [
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'visit_id' => 'visit-existing',
            'landing_url' => 'https://example.com/stretch-ceiling',
            'attribution_referrer' => null,
            'attribution_source' => 'google',
            'attribution_medium' => 'remarketing',
        ]);

        $this->assertDatabaseCount('visits', 1);
        $this->assertDatabaseHas('visits', [
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'landing_url' => null,
            'started_at' => '2026-03-23 10:00:00',
            'last_touched_at' => '2026-03-23 10:10:00',
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'first_attribution_referrer' => null,
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'remarketing',
            'last_attribution_referrer' => null,
        ]);
    }

    public function test_it_creates_revisit_and_reuses_existing_visit_for_direct_revisit(): void
    {
        VisitModel::query()->create([
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'started_at' => '2026-03-23 10:00:00',
            'last_touched_at' => '2026-03-23 10:05:00',
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'cpc',
        ]);

        $occurredAt = new DateTimeImmutable('2026-03-23 10:10:00');
        $command = new RegisterClickCommand(
            new VisitorId('550e8400-e29b-41d4-a716-446655440000'),
            Attribution::direct(),
            'https://example.com/stretch-ceiling',
            $occurredAt,
        );

        $action = $this->app->make(RegisterClickAction::class);
        $result = $action($command);

        $this->assertInstanceOf(RegisterClickResult::class, $result);
        $this->assertSame('visit-existing', $result->visitId);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $result->visitorId);
        $this->assertSame(RegisterClickResult::TYPE_REVISIT, $result->resultType);
        $this->assertNotSame('', $result->resultId);

        $this->assertDatabaseCount('clicks', 0);
        $this->assertDatabaseCount('revisits', 1);
        $this->assertDatabaseHas('revisits', [
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'visit_id' => 'visit-existing',
            'landing_url' => 'https://example.com/stretch-ceiling',
            'occurred_at' => '2026-03-23 10:10:00',
        ]);

        $this->assertDatabaseCount('visits', 1);
        $this->assertDatabaseHas('visits', [
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'landing_url' => null,
            'started_at' => '2026-03-23 10:00:00',
            'last_touched_at' => '2026-03-23 10:10:00',
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'cpc',
        ]);
    }
}
