<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\GetAttributionFunnelReport\GetAttributionFunnelReportQuery;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentAttributionFunnelReportReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use Tests\TestCase;

final class EloquentAttributionFunnelReportReadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_first_touch_acquisition_report_with_raw_clicks_as_reference(): void
    {
        $this->createClick('click-1', 'visitor-1', 'google', 'cpc', 'spring-sale');
        $this->createClick('click-2', 'visitor-2', 'google', 'cpc', 'spring-sale');
        $this->createClick('click-3', 'visitor-3', 'facebook', 'paid-social', 'retargeting');
        $this->createClick('click-4', 'visitor-4', null, null, null);

        $this->createVisit('visit-1', 'visitor-1', 'google', 'cpc', 'spring-sale');
        $this->createVisit('visit-2', 'visitor-2', 'google', 'cpc', 'spring-sale');
        $this->createVisit('visit-3', 'visitor-3', 'facebook', 'paid-social', 'retargeting');

        $this->createLead('lead-1', 'visitor-1', 'visit-1', 'email', 'newsletter', 'reactivation');
        $this->createLead('lead-2', 'visitor-3', 'visit-3', null, null, null);

        $readModel = new EloquentAttributionFunnelReportReadModel();

        $report = $readModel(new GetAttributionFunnelReportQuery());

        $this->assertSame(4, $report->rawClicksCount);
        $this->assertSame(3, $report->visitsCount);
        $this->assertSame(2, $report->leadsCount);
        $this->assertSame(66.67, $report->visitsToLeadsConversionRate);
        $this->assertCount(3, $report->rows);

        $googleRow = $report->rows[0];
        $this->assertSame('google', $googleRow->source);
        $this->assertSame('cpc', $googleRow->medium);
        $this->assertSame('spring-sale', $googleRow->campaign);
        $this->assertSame(2, $googleRow->rawClicksCount);
        $this->assertSame(2, $googleRow->visitsCount);
        $this->assertSame(1, $googleRow->leadsCount);
        $this->assertSame(50.0, $googleRow->visitsToLeadsConversionRate);

        $facebookRow = $report->rows[1];
        $this->assertSame('facebook', $facebookRow->source);
        $this->assertSame('paid-social', $facebookRow->medium);
        $this->assertSame('retargeting', $facebookRow->campaign);
        $this->assertSame(1, $facebookRow->rawClicksCount);
        $this->assertSame(1, $facebookRow->visitsCount);
        $this->assertSame(1, $facebookRow->leadsCount);
        $this->assertSame(100.0, $facebookRow->visitsToLeadsConversionRate);

        $directRow = $report->rows[2];
        $this->assertNull($directRow->source);
        $this->assertNull($directRow->medium);
        $this->assertNull($directRow->campaign);
        $this->assertSame(1, $directRow->rawClicksCount);
        $this->assertSame(0, $directRow->visitsCount);
        $this->assertSame(0, $directRow->leadsCount);
        $this->assertSame(0.0, $directRow->visitsToLeadsConversionRate);
    }

    public function test_it_returns_an_empty_report_when_no_rows_exist(): void
    {
        $readModel = new EloquentAttributionFunnelReportReadModel();

        $report = $readModel(new GetAttributionFunnelReportQuery());

        $this->assertSame(0, $report->rawClicksCount);
        $this->assertSame(0, $report->visitsCount);
        $this->assertSame(0, $report->leadsCount);
        $this->assertSame(0.0, $report->visitsToLeadsConversionRate);
        $this->assertSame([], $report->rows);
    }

    private function createClick(
        string $id,
        string $visitorId,
        ?string $source,
        ?string $medium,
        ?string $campaign,
    ): void {
        ClickModel::query()->create([
            'id' => $id,
            'visitor_id' => $visitorId,
            'landing_url' => 'https://example.com/'.$id,
            'referrer' => null,
            'occurred_at' => '2026-03-29 10:00:00',
            'attribution_source' => $source,
            'attribution_medium' => $medium,
            'attribution_campaign' => $campaign,
        ]);
    }

    private function createVisit(
        string $id,
        string $visitorId,
        ?string $source,
        ?string $medium,
        ?string $campaign,
    ): void {
        VisitModel::query()->create([
            'id' => $id,
            'visitor_id' => $visitorId,
            'started_at' => '2026-03-29 10:00:00',
            'last_touched_at' => '2026-03-29 10:05:00',
            'first_attribution_source' => $source,
            'first_attribution_medium' => $medium,
            'first_attribution_campaign' => $campaign,
            'last_attribution_source' => $source,
            'last_attribution_medium' => $medium,
            'last_attribution_campaign' => $campaign,
        ]);
    }

    private function createLead(
        string $id,
        string $visitorId,
        ?string $visitId,
        ?string $source,
        ?string $medium,
        ?string $campaign,
    ): void {
        LeadModel::query()->create([
            'id' => $id,
            'visitor_id' => $visitorId,
            'visit_id' => $visitId,
            'name' => null,
            'phone' => null,
            'status' => 'new',
            'origin' => 'form',
            'created_at' => '2026-03-29 10:10:00',
            'attribution_source' => $source,
            'attribution_medium' => $medium,
            'attribution_campaign' => $campaign,
        ]);
    }
}
