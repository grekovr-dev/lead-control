<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\GetManagerLeadNotificationQuery;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\ManagerLeadNotificationNotFoundException;
use Inbound\Domain\Lead\LeadId;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentManagerLeadNotificationReadModel;
use Tests\TestCase;

final class EloquentManagerLeadNotificationReadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_the_manager_notification_payload_for_a_lead(): void
    {
        LeadModel::query()->create([
            'id' => 'lead-123',
            'visitor_id' => 'visitor-123',
            'visit_id' => 'visit-123',
            'name' => 'John Doe',
            'phone' => '+380501112233',
            'status' => 'new',
            'origin' => 'form',
            'landing_url' => 'https://example.com/landing',
            'created_at' => '2026-04-08 09:00:00',
        ]);

        $readModel = new EloquentManagerLeadNotificationReadModel;

        $result = $readModel(new GetManagerLeadNotificationQuery(new LeadId('lead-123')));

        $this->assertSame('lead-123', $result->leadId);
        $this->assertSame('John Doe', $result->name);
        $this->assertSame('+380501112233', $result->phone);
        $this->assertSame('form', $result->origin);
        $this->assertSame('https://example.com/landing', $result->landingUrl);
    }

    public function test_it_returns_the_payload_for_legacy_rows_without_optional_fields(): void
    {
        LeadModel::query()->create([
            'id' => 'lead-legacy',
            'visitor_id' => 'visitor-legacy',
            'visit_id' => 'visit-legacy',
            'name' => null,
            'phone' => '+380501119900',
            'status' => 'new',
            'origin' => 'phone_click',
            'landing_url' => null,
            'created_at' => '2026-04-08 10:00:00',
        ]);

        $readModel = new EloquentManagerLeadNotificationReadModel;

        $result = $readModel(new GetManagerLeadNotificationQuery(new LeadId('lead-legacy')));

        $this->assertNull($result->name);
        $this->assertNull($result->landingUrl);
        $this->assertSame('+380501119900', $result->phone);
        $this->assertSame('phone_click', $result->origin);
    }

    public function test_it_throws_when_the_lead_is_missing(): void
    {
        $readModel = new EloquentManagerLeadNotificationReadModel;

        $this->expectException(ManagerLeadNotificationNotFoundException::class);

        $readModel(new GetManagerLeadNotificationQuery(new LeadId('missing-lead')));
    }
}
