<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\GetVisitAttributionFunnelReportQuery;
use Inbound\Domain\Shared\DateRange;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentVisitAttributionFunnelReportReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use Tests\TestCase;

final class EloquentVisitAttributionFunnelReportReadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_a_visit_attribution_report_with_raw_clicks_as_reference(): void
    {
        $this->createClick('click-1', 'visitor-1', 'visit-1', 'google', 'cpc', 'spring-sale', '2026-03-29 10:00:00');
        $this->createClick('click-2', 'visitor-2', 'visit-2', 'google', 'cpc', 'spring-sale', '2026-03-29 10:01:00');
        $this->createClick('click-3', 'visitor-3', 'visit-3', 'facebook', 'paid-social', 'retargeting', '2026-03-29 10:02:00');
        $this->createClick('click-4', 'visitor-4', null, null, null, null, '2026-03-29 10:03:00');

        $this->createVisit('visit-1', 'visitor-1', 'google', 'cpc', 'spring-sale', '2026-03-29 10:00:00');
        $this->createVisit('visit-2', 'visitor-2', 'google', 'cpc', 'spring-sale', '2026-03-29 10:01:00');
        $this->createVisit('visit-3', 'visitor-3', 'facebook', 'paid-social', 'retargeting', '2026-03-29 10:02:00');

        $this->createLead('lead-1', 'visitor-1', 'visit-1', 'google', 'cpc', 'spring-sale', '2026-03-29 10:10:00');
        $this->createLead('lead-2', 'visitor-3', 'visit-3', 'facebook', 'paid-social', 'retargeting', '2026-03-29 10:11:00');

        $readModel = new EloquentVisitAttributionFunnelReportReadModel;

        $report = $readModel(new GetVisitAttributionFunnelReportQuery);

        $this->assertSame(4, $report->rawClicksCount);
        $this->assertSame(3, $report->visitsCount);
        $this->assertSame(2, $report->leadsCount);
        $this->assertSame(1.33, $report->rawClicksPerVisitRate);
        $this->assertSame(66.67, $report->visitsToLeadsConversionRate);
        $this->assertCount(3, $report->rows);

        $googleRow = $this->findRowBySource($report, 'google');
        $this->assertSame('google', $googleRow->source);
        $this->assertSame('cpc', $googleRow->medium);
        $this->assertSame('spring-sale', $googleRow->campaign);
        $this->assertSame(2, $googleRow->rawClicksCount);
        $this->assertSame(2, $googleRow->visitsCount);
        $this->assertSame(1, $googleRow->leadsCount);
        $this->assertSame(1.0, $googleRow->rawClicksPerVisitRate);
        $this->assertSame(50.0, $googleRow->visitsToLeadsConversionRate);

        $facebookRow = $this->findRowBySource($report, 'facebook');
        $this->assertSame('facebook', $facebookRow->source);
        $this->assertSame('paid-social', $facebookRow->medium);
        $this->assertSame('retargeting', $facebookRow->campaign);
        $this->assertSame(1, $facebookRow->rawClicksCount);
        $this->assertSame(1, $facebookRow->visitsCount);
        $this->assertSame(1, $facebookRow->leadsCount);
        $this->assertSame(1.0, $facebookRow->rawClicksPerVisitRate);
        $this->assertSame(100.0, $facebookRow->visitsToLeadsConversionRate);

        $directRow = $this->findRowBySource($report, null);
        $this->assertNull($directRow->source);
        $this->assertNull($directRow->medium);
        $this->assertNull($directRow->campaign);
        $this->assertSame(1, $directRow->rawClicksCount);
        $this->assertSame(0, $directRow->visitsCount);
        $this->assertSame(0, $directRow->leadsCount);
        $this->assertSame(0.0, $directRow->rawClicksPerVisitRate);
        $this->assertSame(0.0, $directRow->visitsToLeadsConversionRate);
    }

    public function test_it_filters_clicks_and_visits_by_period_and_counts_leads_from_selected_visits(): void
    {
        $this->createClick('click-1', 'visitor-1', 'visit-1', 'google', 'cpc', 'spring-sale', '2026-03-10 09:00:00');
        $this->createClick('click-2', 'visitor-2', 'visit-2', 'facebook', 'paid-social', 'retargeting', '2026-03-20 12:00:00');
        $this->createClick('click-3', 'visitor-3', 'visit-3', 'newsletter', 'email', 'warmup', '2026-03-09 23:59:59');

        $this->createVisit('visit-1', 'visitor-1', 'google', 'cpc', 'spring-sale', '2026-03-10 09:00:00');
        $this->createVisit('visit-2', 'visitor-2', 'facebook', 'paid-social', 'retargeting', '2026-03-20 12:00:00');
        $this->createVisit('visit-3', 'visitor-3', 'newsletter', 'email', 'warmup', '2026-03-09 23:59:59');

        $this->createLead('lead-1', 'visitor-1', 'visit-1', 'google', 'cpc', 'spring-sale', '2026-04-02 09:00:00');
        $this->createLead('lead-2', 'visitor-2', 'visit-2', 'facebook', 'paid-social', 'retargeting', '2026-03-20 12:30:00');
        $this->createLead('lead-3', 'visitor-3', 'visit-3', 'newsletter', 'email', 'warmup', '2026-03-20 13:00:00');

        $readModel = new EloquentVisitAttributionFunnelReportReadModel;

        $report = $readModel(new GetVisitAttributionFunnelReportQuery(
            reportPeriod: new DateRange(
                fromInclusive: new \DateTimeImmutable('2026-03-10 00:00:00'),
                toExclusive: new \DateTimeImmutable('2026-03-21 00:00:00'),
            ),
        ));

        $this->assertSame(2, $report->rawClicksCount);
        $this->assertSame(2, $report->visitsCount);
        $this->assertSame(2, $report->leadsCount);
        $this->assertCount(2, $report->rows);

        $googleRow = $this->findRowBySource($report, 'google');
        $this->assertSame('google', $googleRow->source);
        $this->assertSame(1, $googleRow->rawClicksCount);
        $this->assertSame(1, $googleRow->visitsCount);
        $this->assertSame(1, $googleRow->leadsCount);

        $facebookRow = $this->findRowBySource($report, 'facebook');
        $this->assertSame('facebook', $facebookRow->source);
        $this->assertSame(1, $facebookRow->rawClicksCount);
        $this->assertSame(1, $facebookRow->visitsCount);
        $this->assertSame(1, $facebookRow->leadsCount);
    }

    public function test_it_returns_an_empty_report_when_no_rows_exist(): void
    {
        $readModel = new EloquentVisitAttributionFunnelReportReadModel;

        $report = $readModel(new GetVisitAttributionFunnelReportQuery);

        $this->assertSame(0, $report->rawClicksCount);
        $this->assertSame(0, $report->visitsCount);
        $this->assertSame(0, $report->leadsCount);
        $this->assertSame(0.0, $report->rawClicksPerVisitRate);
        $this->assertSame(0.0, $report->visitsToLeadsConversionRate);
        $this->assertSame([], $report->rows);
    }

    private function createClick(
        string $id,
        string $visitorId,
        ?string $visitId,
        ?string $source,
        ?string $medium,
        ?string $campaign,
        string $occurredAt,
    ): void {
        ClickModel::query()->create([
            'id' => $id,
            'visitor_id' => $visitorId,
            'visit_id' => $visitId,
            'landing_url' => 'https://example.com/'.$id,
            'attribution_referrer' => null,
            'occurred_at' => $occurredAt,
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
        string $startedAt,
    ): void {
        VisitModel::query()->create([
            'id' => $id,
            'visitor_id' => $visitorId,
            'started_at' => $startedAt,
            'last_touched_at' => $startedAt,
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
        string $createdAt,
    ): void {
        LeadModel::query()->create([
            'id' => $id,
            'visitor_id' => $visitorId,
            'visit_id' => $visitId,
            'name' => null,
            'phone' => null,
            'status' => 'new',
            'origin' => 'form',
            'created_at' => $createdAt,
            'visit_attribution_source' => $source,
            'visit_attribution_medium' => $medium,
            'visit_attribution_campaign' => $campaign,
        ]);
    }

    private function findRowBySource(
        \Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\VisitAttributionFunnelReportView $report,
        ?string $source,
    ): \Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\VisitAttributionFunnelReportRowView {
        foreach ($report->rows as $row) {
            if ($row->source === $source) {
                return $row;
            }
        }

        $this->fail('Expected report row was not found.');
    }
}
