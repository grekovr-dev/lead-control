<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Application\Actions\Capture\CreateLeadFromForm;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Actions\Capture\CreateLeadFromForm\CreateLeadFromFormAction;
use Inbound\Application\Actions\Capture\CreateLeadFromForm\CreateLeadFromFormCommand;
use Inbound\Application\Actions\Capture\CreateLeadFromForm\CurrentVisitNotFoundException;
use Inbound\Domain\Lead\Lead;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use Tests\TestCase;

final class CreateLeadFromFormActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_lead_using_existing_active_visit(): void
    {
        VisitModel::query()->create([
            'id' => 'visit-first',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'landing_url' => 'https://example.com/first-landing',
            'started_at' => '2026-03-23 11:00:00',
            'last_touched_at' => '2026-03-23 11:10:00',
            'first_attribution_source' => 'facebook',
            'first_attribution_medium' => 'paid-social',
            'last_attribution_source' => 'facebook',
            'last_attribution_medium' => 'paid-social',
        ]);

        VisitModel::query()->create([
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'landing_url' => 'https://example.com/current-landing',
            'started_at' => '2026-03-23 12:00:00',
            'last_touched_at' => '2026-03-23 12:05:00',
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'remarketing',
        ]);

        $occurredAt = new DateTimeImmutable('2026-03-23 12:10:00');
        $command = new CreateLeadFromFormCommand(
            new VisitorId('550e8400-e29b-41d4-a716-446655440000'),
            ' John Doe ',
            ' +380501112233 ',
            $occurredAt,
        );

        $action = $this->app->make(CreateLeadFromFormAction::class);
        $lead = $action($command);
        $leadId = $lead->id()->value();

        $this->assertInstanceOf(Lead::class, $lead);
        $this->assertNotSame('', $leadId);
        $this->assertSame($leadId, $lead->id()->value());
        $this->assertSame('visit-existing', $lead->visitId()->value());
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $lead->visitorId()->value());
        $this->assertSame('John Doe', $lead->name());
        $this->assertSame('+380501112233', $lead->phone());
        $this->assertSame('google', $lead->visitAttribution()->source());
        $this->assertSame('cpc', $lead->visitAttribution()->medium());
        $this->assertSame('facebook', $lead->visitorAttribution()->source());
        $this->assertSame('paid-social', $lead->visitorAttribution()->medium());
        $this->assertSame('https://example.com/current-landing', $lead->landingUrl());
        $this->assertSame('form', $lead->origin());
        $this->assertSame('new', $lead->status()->value);

        $this->assertDatabaseCount('leads', 1);
        $this->assertDatabaseHas('leads', [
            'id' => $leadId,
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'visit_id' => 'visit-existing',
            'name' => 'John Doe',
            'phone' => '+380501112233',
            'status' => 'new',
            'origin' => 'form',
            'landing_url' => 'https://example.com/current-landing',
            'created_at' => '2026-03-23 12:10:00',
            'visit_attribution_source' => 'google',
            'visit_attribution_medium' => 'cpc',
            'visitor_attribution_source' => 'facebook',
            'visitor_attribution_medium' => 'paid-social',
        ]);

        $this->assertDatabaseCount('visits', 2);
    }

    public function test_it_throws_when_active_visit_is_missing(): void
    {
        $command = new CreateLeadFromFormCommand(
            new VisitorId('550e8400-e29b-41d4-a716-446655440000'),
            'John Doe',
            '+380501112233',
            new DateTimeImmutable('2026-03-23 12:10:00'),
        );

        $action = $this->app->make(CreateLeadFromFormAction::class);

        $this->expectException(CurrentVisitNotFoundException::class);
        $this->expectExceptionMessage('Cannot create lead from form without a current visit.');

        $action($command);

        $this->assertDatabaseCount('leads', 0);
    }

    public function test_it_continues_last_visit_even_when_session_is_expired(): void
    {
        VisitModel::query()->create([
            'id' => 'visit-expired',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'landing_url' => 'https://example.com/expired-landing',
            'started_at' => '2026-03-23 12:00:00',
            'last_touched_at' => '2026-03-23 12:10:00',
            'first_attribution_source' => 'google',
            'last_attribution_source' => 'google',
        ]);

        $occurredAt = new DateTimeImmutable('2026-03-23 12:40:01');
        $command = new CreateLeadFromFormCommand(
            new VisitorId('550e8400-e29b-41d4-a716-446655440000'),
            'John Doe',
            '+380501112233',
            $occurredAt,
        );

        $action = $this->app->make(CreateLeadFromFormAction::class);
        $lead = $action($command);
        $leadId = $lead->id()->value();

        $this->assertInstanceOf(Lead::class, $lead);
        $this->assertNotSame('', $leadId);
        $this->assertSame($leadId, $lead->id()->value());
        $this->assertSame('visit-expired', $lead->visitId()->value());
        $this->assertSame('form', $lead->origin());

        $this->assertDatabaseCount('leads', 1);
        $this->assertDatabaseHas('leads', [
            'id' => $leadId,
            'visit_id' => 'visit-expired',
            'origin' => 'form',
            'landing_url' => 'https://example.com/expired-landing',
            'created_at' => '2026-03-23 12:40:01',
        ]);
        $this->assertDatabaseHas('visits', [
            'id' => 'visit-expired',
            'last_touched_at' => '2026-03-23 12:40:01',
        ]);
    }
}
