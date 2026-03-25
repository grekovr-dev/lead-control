<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Application\Actions\Capture\CreateLeadFromPhoneClick;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Actions\Capture\CreateLeadFromPhoneClick\ActiveVisitNotFoundException;
use Inbound\Application\Actions\Capture\CreateLeadFromPhoneClick\CreateLeadFromPhoneClickAction;
use Inbound\Application\Actions\Capture\CreateLeadFromPhoneClick\CreateLeadFromPhoneClickCommand;
use Inbound\Domain\Lead\Lead;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use Tests\TestCase;

final class CreateLeadFromPhoneClickActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_lead_using_existing_active_visit(): void
    {
        VisitModel::query()->create([
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'started_at' => '2026-03-23 13:00:00',
            'last_touched_at' => '2026-03-23 13:05:00',
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'remarketing',
        ]);

        $occurredAt = new DateTimeImmutable('2026-03-23 13:10:00');
        $command = new CreateLeadFromPhoneClickCommand(
            new LeadId('lead-phone-click'),
            new VisitorId('550e8400-e29b-41d4-a716-446655440000'),
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            $occurredAt,
        );

        $action = $this->app->make(CreateLeadFromPhoneClickAction::class);
        $lead = $action($command);

        $this->assertInstanceOf(Lead::class, $lead);
        $this->assertSame('lead-phone-click', $lead->id()->value());
        $this->assertSame('visit-existing', $lead->visitId()->value());
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $lead->visitorId()->value());
        $this->assertNull($lead->name());
        $this->assertNull($lead->phone());
        $this->assertSame('phone_click', $lead->origin());
        $this->assertSame('new', $lead->status()->value);

        $this->assertDatabaseCount('leads', 1);
        $this->assertDatabaseHas('leads', [
            'id' => 'lead-phone-click',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'visit_id' => 'visit-existing',
            'name' => null,
            'phone' => null,
            'status' => 'new',
            'origin' => 'phone_click',
            'created_at' => '2026-03-23 13:10:00',
            'attribution_source' => 'google',
            'attribution_medium' => 'cpc',
        ]);

        $this->assertDatabaseCount('visits', 1);
    }

    public function test_it_throws_when_active_visit_is_missing(): void
    {
        $command = new CreateLeadFromPhoneClickCommand(
            new LeadId('lead-missing-visit'),
            new VisitorId('550e8400-e29b-41d4-a716-446655440000'),
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            new DateTimeImmutable('2026-03-23 13:10:00'),
        );

        $action = $this->app->make(CreateLeadFromPhoneClickAction::class);

        $this->expectException(ActiveVisitNotFoundException::class);
        $this->expectExceptionMessage('Cannot create lead from phone click without an active visit.');

        $action($command);
    }

    public function test_it_throws_when_last_visit_session_is_expired(): void
    {
        VisitModel::query()->create([
            'id' => 'visit-expired',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'started_at' => '2026-03-23 13:00:00',
            'last_touched_at' => '2026-03-23 13:10:00',
            'first_attribution_source' => 'google',
            'last_attribution_source' => 'google',
        ]);

        $command = new CreateLeadFromPhoneClickCommand(
            new LeadId('lead-expired'),
            new VisitorId('550e8400-e29b-41d4-a716-446655440000'),
            new Attribution('google', 'cpc', null, null, null, null, null, null),
            new DateTimeImmutable('2026-03-23 13:40:01'),
        );

        $action = $this->app->make(CreateLeadFromPhoneClickAction::class);

        $this->expectException(ActiveVisitNotFoundException::class);
        $this->expectExceptionMessage('Cannot create lead from phone click without an active visit.');

        $action($command);
    }
}
