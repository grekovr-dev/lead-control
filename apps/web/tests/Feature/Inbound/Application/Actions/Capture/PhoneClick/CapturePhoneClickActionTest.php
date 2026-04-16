<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Application\Actions\Capture\PhoneClick;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Actions\Capture\PhoneClick\CapturePhoneClickAction;
use Inbound\Application\Actions\Capture\PhoneClick\CapturePhoneClickCommand;
use Inbound\Application\Actions\Capture\PhoneClick\CapturePhoneClickResult;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use Tests\TestCase;

final class CapturePhoneClickActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_touch_when_phone_click_lead_already_exists_in_current_visit(): void
    {
        VisitModel::query()->create([
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'landing_url' => 'https://example.com/landing',
            'started_at' => '2026-03-23 11:00:00',
            'last_touched_at' => '2026-03-23 11:05:00',
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'remarketing',
        ]);

        LeadModel::query()->create([
            'id' => 'lead-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'visit_id' => 'visit-existing',
            'name' => null,
            'phone' => null,
            'status' => 'new',
            'origin' => 'phone_click',
            'created_at' => '2026-03-23 11:06:00',
            'landing_url' => 'https://example.com/landing',
            'visit_attribution_source' => 'google',
            'visit_attribution_medium' => 'cpc',
            'visitor_attribution_source' => 'google',
            'visitor_attribution_medium' => 'cpc',
        ]);

        $occurredAt = new DateTimeImmutable('2026-03-23 11:10:00');
        $command = new CapturePhoneClickCommand(
            new VisitorId('550e8400-e29b-41d4-a716-446655440000'),
            $occurredAt,
        );

        $action = $this->app->make(CapturePhoneClickAction::class);
        $result = $action($command);

        $this->assertInstanceOf(CapturePhoneClickResult::class, $result);
        $this->assertSame(CapturePhoneClickResult::TYPE_TOUCH, $result->resultType);
        $this->assertNotSame('', $result->resultId);
        $this->assertSame('visit-existing', $result->visitId);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $result->visitorId);
        $this->assertSame($result->resultId, $result->resultId);

        $this->assertDatabaseCount('leads', 1);
        $this->assertDatabaseCount('touches', 1);
        $this->assertDatabaseHas('touches', [
            'id' => $result->resultId,
            'visit_id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'type' => 'phone_click',
            'occurred_at' => '2026-03-23 11:10:00',
        ]);
        $this->assertDatabaseHas('visits', [
            'id' => 'visit-existing',
            'last_touched_at' => '2026-03-23 11:10:00',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'remarketing',
        ]);
    }
}
