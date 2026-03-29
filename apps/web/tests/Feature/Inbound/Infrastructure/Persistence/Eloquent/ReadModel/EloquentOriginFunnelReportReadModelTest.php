<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\GetOriginFunnelReportQuery;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentOriginFunnelReportReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\TouchModel;
use Tests\TestCase;

final class EloquentOriginFunnelReportReadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_origin_funnel_aggregates_from_mappable_touches_and_leads(): void
    {
        $this->createTouch('touch-1', 'visit-1', 'visitor-1', 'lead_form_click');
        $this->createTouch('touch-2', 'visit-2', 'visitor-2', 'lead_form_click');
        $this->createTouch('touch-3', 'visit-3', 'visitor-3', 'phone_click');
        $this->createTouch('touch-4', 'visit-4', 'visitor-4', 'messenger_click');
        $this->createTouch('touch-5', 'visit-5', 'visitor-5', 'works_click');

        $this->createLead('lead-1', 'visitor-1', 'visit-1', 'form');
        $this->createLead('lead-2', 'visitor-3', 'visit-3', 'phone_click');

        $readModel = new EloquentOriginFunnelReportReadModel();

        $report = $readModel(new GetOriginFunnelReportQuery());

        $this->assertSame(4, $report->touchesCount);
        $this->assertSame(2, $report->leadsCount);
        $this->assertSame(50.0, $report->touchesToLeadsConversionRate);
        $this->assertCount(3, $report->rows);

        $formRow = $report->rows[0];
        $this->assertSame('form', $formRow->origin);
        $this->assertSame('Форма', $formRow->originLabel);
        $this->assertSame(2, $formRow->touchesCount);
        $this->assertSame(1, $formRow->leadsCount);
        $this->assertSame(50.0, $formRow->touchesToLeadsConversionRate);

        $phoneRow = $report->rows[1];
        $this->assertSame('phone_click', $phoneRow->origin);
        $this->assertSame('Клік по телефону', $phoneRow->originLabel);
        $this->assertSame(1, $phoneRow->touchesCount);
        $this->assertSame(1, $phoneRow->leadsCount);
        $this->assertSame(100.0, $phoneRow->touchesToLeadsConversionRate);

        $messengerRow = $report->rows[2];
        $this->assertSame('messenger_click', $messengerRow->origin);
        $this->assertSame('Клік по месенджеру', $messengerRow->originLabel);
        $this->assertSame(1, $messengerRow->touchesCount);
        $this->assertSame(0, $messengerRow->leadsCount);
        $this->assertSame(0.0, $messengerRow->touchesToLeadsConversionRate);
    }

    public function test_it_returns_an_empty_origin_report_when_no_mappable_data_exists(): void
    {
        $this->createTouch('touch-1', 'visit-1', 'visitor-1', 'works_click');

        $readModel = new EloquentOriginFunnelReportReadModel();

        $report = $readModel(new GetOriginFunnelReportQuery());

        $this->assertSame(0, $report->touchesCount);
        $this->assertSame(0, $report->leadsCount);
        $this->assertSame(0.0, $report->touchesToLeadsConversionRate);
        $this->assertSame([], $report->rows);
    }

    private function createTouch(string $id, string $visitId, string $visitorId, string $type): void
    {
        TouchModel::query()->create([
            'id' => $id,
            'visit_id' => $visitId,
            'visitor_id' => $visitorId,
            'type' => $type,
            'occurred_at' => '2026-03-29 11:00:00',
        ]);
    }

    private function createLead(string $id, string $visitorId, ?string $visitId, string $origin): void
    {
        LeadModel::query()->create([
            'id' => $id,
            'visitor_id' => $visitorId,
            'visit_id' => $visitId,
            'name' => null,
            'phone' => null,
            'status' => 'new',
            'origin' => $origin,
            'created_at' => '2026-03-29 11:10:00',
        ]);
    }
}
