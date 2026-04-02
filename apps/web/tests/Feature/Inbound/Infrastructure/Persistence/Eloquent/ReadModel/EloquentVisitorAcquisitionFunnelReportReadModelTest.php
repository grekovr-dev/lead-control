<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\GetVisitorAcquisitionFunnelReportQuery;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\VisitorAcquisitionFunnelReportRowView;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\VisitorAcquisitionFunnelReportView;
use Inbound\Domain\Shared\DateRange;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentVisitorAcquisitionFunnelReportReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use Tests\TestCase;

final class EloquentVisitorAcquisitionFunnelReportReadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_visitor_acquisition_rows_from_first_visit_cohorts_and_later_leads(): void
    {
        $this->createVisit('visit-1', 'visitor-1', 'google', 'cpc', 'spring-sale', '2026-03-01 09:00:00');
        $this->createVisit('visit-2', 'visitor-1', 'direct', null, null, '2026-03-15 11:00:00');
        $this->createVisit('visit-3', 'visitor-2', 'facebook', 'paid-social', 'lookalike', '2026-03-02 10:00:00');
        $this->createVisit('visit-4', 'visitor-3', null, null, null, '2026-03-03 12:00:00');

        $this->createLead('lead-1', 'visitor-1', 'visit-2', 'google', 'cpc', 'spring-sale', '2026-04-10 08:00:00');
        $this->createLead('lead-2', 'visitor-2', 'visit-3', 'facebook', 'paid-social', 'lookalike', '2026-03-05 14:00:00');

        $readModel = new EloquentVisitorAcquisitionFunnelReportReadModel;

        $report = $readModel(new GetVisitorAcquisitionFunnelReportQuery);

        $this->assertSame(3, $report->visitorsCount);
        $this->assertSame(2, $report->leadsCount);
        $this->assertSame(66.67, $report->visitorsToLeadsConversionRate);
        $this->assertCount(3, $report->rows);

        $googleRow = $this->findRowBySource($report, 'google');
        $this->assertSame('google', $googleRow->visitorAttributionSource);
        $this->assertSame('cpc', $googleRow->visitorAttributionMedium);
        $this->assertSame('spring-sale', $googleRow->visitorAttributionCampaign);
        $this->assertSame(1, $googleRow->visitorsCount);
        $this->assertSame(1, $googleRow->leadsCount);
        $this->assertSame(100.0, $googleRow->visitorsToLeadsConversionRate);

        $facebookRow = $this->findRowBySource($report, 'facebook');
        $this->assertSame('facebook', $facebookRow->visitorAttributionSource);
        $this->assertSame('paid-social', $facebookRow->visitorAttributionMedium);
        $this->assertSame('lookalike', $facebookRow->visitorAttributionCampaign);
        $this->assertSame(1, $facebookRow->visitorsCount);
        $this->assertSame(1, $facebookRow->leadsCount);
        $this->assertSame(100.0, $facebookRow->visitorsToLeadsConversionRate);

        $directRow = $this->findRowBySource($report, null);
        $this->assertNull($directRow->visitorAttributionSource);
        $this->assertNull($directRow->visitorAttributionMedium);
        $this->assertNull($directRow->visitorAttributionCampaign);
        $this->assertSame(1, $directRow->visitorsCount);
        $this->assertSame(0, $directRow->leadsCount);
        $this->assertSame(0.0, $directRow->visitorsToLeadsConversionRate);
    }

    public function test_it_filters_by_first_visit_period_and_keeps_leads_created_later(): void
    {
        $this->createVisit('visit-1', 'visitor-1', 'google', 'cpc', 'spring-sale', '2026-03-09 23:59:59');
        $this->createVisit('visit-2', 'visitor-1', 'newsletter', 'email', 'warmup', '2026-03-20 10:00:00');
        $this->createVisit('visit-3', 'visitor-2', 'facebook', 'paid-social', 'lookalike', '2026-03-10 09:00:00');
        $this->createVisit('visit-4', 'visitor-3', 'google', 'organic', null, '2026-03-20 12:00:00');

        $this->createLead('lead-1', 'visitor-1', 'visit-2', 'google', 'cpc', 'spring-sale', '2026-03-20 18:00:00');
        $this->createLead('lead-2', 'visitor-2', 'visit-3', 'facebook', 'paid-social', 'lookalike', '2026-04-05 09:00:00');
        $this->createLead('lead-3', 'visitor-3', 'visit-4', 'google', 'organic', null, '2026-03-20 18:05:00');

        $readModel = new EloquentVisitorAcquisitionFunnelReportReadModel;

        $report = $readModel(new GetVisitorAcquisitionFunnelReportQuery(
            firstVisitPeriod: new DateRange(
                fromInclusive: new \DateTimeImmutable('2026-03-10 00:00:00'),
                toExclusive: new \DateTimeImmutable('2026-03-21 00:00:00'),
            ),
        ));

        $this->assertSame(2, $report->visitorsCount);
        $this->assertSame(2, $report->leadsCount);
        $this->assertSame(100.0, $report->visitorsToLeadsConversionRate);
        $this->assertCount(2, $report->rows);

        $facebookRow = $this->findRowBySource($report, 'facebook');
        $this->assertSame(1, $facebookRow->visitorsCount);
        $this->assertSame(1, $facebookRow->leadsCount);

        $organicRow = $this->findRowBySource($report, 'google');
        $this->assertSame('organic', $organicRow->visitorAttributionMedium);
        $this->assertNull($organicRow->visitorAttributionCampaign);
        $this->assertSame(1, $organicRow->visitorsCount);
        $this->assertSame(1, $organicRow->leadsCount);
    }

    public function test_it_returns_an_empty_report_when_no_cohort_matches(): void
    {
        $readModel = new EloquentVisitorAcquisitionFunnelReportReadModel;

        $report = $readModel(new GetVisitorAcquisitionFunnelReportQuery);

        $this->assertSame(0, $report->visitorsCount);
        $this->assertSame(0, $report->leadsCount);
        $this->assertSame(0.0, $report->visitorsToLeadsConversionRate);
        $this->assertSame([], $report->rows);
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
        ?string $visitorAttributionSource,
        ?string $visitorAttributionMedium,
        ?string $visitorAttributionCampaign,
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
            'visit_attribution_source' => null,
            'visit_attribution_medium' => null,
            'visit_attribution_campaign' => null,
            'visitor_attribution_source' => $visitorAttributionSource,
            'visitor_attribution_medium' => $visitorAttributionMedium,
            'visitor_attribution_campaign' => $visitorAttributionCampaign,
        ]);
    }

    private function findRowBySource(
        VisitorAcquisitionFunnelReportView $report,
        ?string $source,
    ): VisitorAcquisitionFunnelReportRowView {
        foreach ($report->rows as $row) {
            if ($row->visitorAttributionSource === $source) {
                return $row;
            }
        }

        $this->fail('Expected report row was not found.');
    }
}
