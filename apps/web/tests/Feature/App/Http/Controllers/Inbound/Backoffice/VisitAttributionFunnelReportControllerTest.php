<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Backoffice;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\GetVisitAttributionFunnelReportQuery;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\VisitAttributionFunnelReportReadModel;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\VisitAttributionFunnelReportRowView;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\VisitAttributionFunnelReportView;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use Mockery;
use Tests\TestCase;

final class VisitAttributionFunnelReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_renders_the_visit_attribution_funnel_report_screen(): void
    {
        $readModel = Mockery::mock(VisitAttributionFunnelReportReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::type(GetVisitAttributionFunnelReportQuery::class))
            ->andReturn(new VisitAttributionFunnelReportView(
                rawClicksCount: 14,
                visitsCount: 10,
                leadsCount: 4,
                rawClicksPerVisitRate: 1.4,
                visitsToLeadsConversionRate: 40.0,
                rows: [
                    new VisitAttributionFunnelReportRowView(
                        source: 'google',
                        medium: 'cpc',
                        campaign: 'spring-sale',
                        rawClicksCount: 8,
                        visitsCount: 5,
                        leadsCount: 2,
                        rawClicksPerVisitRate: 1.6,
                        visitsToLeadsConversionRate: 40.0,
                    ),
                    new VisitAttributionFunnelReportRowView(
                        source: null,
                        medium: null,
                        campaign: null,
                        rawClicksCount: 6,
                        visitsCount: 5,
                        leadsCount: 2,
                        rawClicksPerVisitRate: 1.2,
                        visitsToLeadsConversionRate: 40.0,
                    ),
                ],
            ));

        $this->app->instance(VisitAttributionFunnelReportReadModel::class, $readModel);

        $this->get(route('admin.reports.visit-attribution-funnel'))
            ->assertOk()
            ->assertViewIs('admin.reports.visit-attribution-funnel')
            ->assertSeeText([
                'Воронка атрибуції візитів',
                'Звіт за атрибуцією візитів',
                'На відміну від воронки залучення відвідувачів',
                'Пресет',
                'Період',
                'Сирі кліки',
                'Візити',
                'Ліди',
                'Кліків на візит',
                'Конверсія візитів у ліди',
                'Основний KPI',
                'google',
                'cpc',
                'spring-sale',
                'Без атрибуції',
                'Без кампанії',
                '1,40',
                '1,60',
                '1,20',
                '40,00%',
            ]);
    }

    public function test_it_filters_the_report_by_last_7_days_and_counts_leads_from_selected_visits(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-30 10:15:00', 'Europe/Kyiv'));

        $this->createVisit('visit-1', 'visitor-1', 'google', 'cpc', 'spring-sale', '2026-03-24 09:00:00');
        $this->createVisit('visit-2', 'visitor-2', 'facebook', 'paid-social', 'retargeting', '2026-03-30 08:00:00');
        $this->createVisit('visit-3', 'visitor-3', 'newsletter', 'email', 'warmup', '2026-03-23 23:59:59');

        $this->createClick('click-1', 'visitor-1', 'visit-1', 'google', 'cpc', 'spring-sale', '2026-03-24 09:00:00');
        $this->createClick('click-2', 'visitor-2', 'visit-2', 'facebook', 'paid-social', 'retargeting', '2026-03-30 08:00:00');
        $this->createClick('click-3', 'visitor-3', 'visit-3', 'newsletter', 'email', 'warmup', '2026-03-23 23:59:59');

        $this->createLead('lead-1', 'visitor-1', 'visit-1', 'google', 'cpc', 'spring-sale', '2026-04-05 12:00:00');
        $this->createLead('lead-2', 'visitor-2', 'visit-2', 'facebook', 'paid-social', 'retargeting', '2026-03-30 09:00:00');
        $this->createLead('lead-3', 'visitor-3', 'visit-3', 'newsletter', 'email', 'warmup', '2026-03-30 09:30:00');

        $response = $this->get(route('admin.reports.visit-attribution-funnel', [
            'preset' => 'last_7_days',
        ]));

        $response->assertOk();
        $response->assertViewHas('filters', fn (array $filters): bool => $filters === [
            'preset' => 'last_7_days',
            'from' => null,
            'to' => null,
        ]);
        $response->assertSee('option value="last_7_days" selected', false);
        $response->assertSeeText([
            'Період звіту',
            '2',
        ]);
        $response->assertDontSeeText('warmup');
    }

    public function test_it_filters_the_report_by_custom_range_and_keeps_leads_even_if_they_were_created_later(): void
    {
        $this->createVisit('visit-1', 'visitor-1', 'google', 'cpc', 'spring-sale', '2026-03-10 09:00:00');
        $this->createVisit('visit-2', 'visitor-2', 'facebook', 'paid-social', 'retargeting', '2026-03-20 18:00:00');
        $this->createVisit('visit-3', 'visitor-3', 'newsletter', 'email', 'warmup', '2026-03-21 00:00:00');

        $this->createClick('click-1', 'visitor-1', 'visit-1', 'google', 'cpc', 'spring-sale', '2026-03-10 09:00:00');
        $this->createClick('click-2', 'visitor-2', 'visit-2', 'facebook', 'paid-social', 'retargeting', '2026-03-20 18:00:00');
        $this->createClick('click-3', 'visitor-3', 'visit-3', 'newsletter', 'email', 'warmup', '2026-03-21 00:00:00');

        $this->createLead('lead-1', 'visitor-1', 'visit-1', 'google', 'cpc', 'spring-sale', '2026-04-03 09:00:00');
        $this->createLead('lead-2', 'visitor-2', 'visit-2', 'facebook', 'paid-social', 'retargeting', '2026-03-20 18:05:00');
        $this->createLead('lead-3', 'visitor-3', 'visit-3', 'newsletter', 'email', 'warmup', '2026-03-20 19:00:00');

        $response = $this->get(route('admin.reports.visit-attribution-funnel', [
            'preset' => 'custom',
            'from' => '2026-03-10',
            'to' => '2026-03-20',
        ]));

        $response->assertOk();
        $response->assertViewHas('filters', fn (array $filters): bool => $filters === [
            'preset' => 'custom',
            'from' => '2026-03-10',
            'to' => '2026-03-20',
        ]);
        $response->assertSee('option value="custom" selected', false);
        $response->assertSee('value="2026-03-10"', false);
        $response->assertSee('value="2026-03-20"', false);
        $response->assertSeeText([
            'google',
            'facebook',
        ]);
        $response->assertDontSeeText('newsletter');
    }

    public function test_it_adds_honest_drill_links_for_clicks_and_visits_including_missing_bucket_dimensions(): void
    {
        $readModel = Mockery::mock(VisitAttributionFunnelReportReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::type(GetVisitAttributionFunnelReportQuery::class))
            ->andReturn(new VisitAttributionFunnelReportView(
                rawClicksCount: 14,
                visitsCount: 10,
                leadsCount: 4,
                rawClicksPerVisitRate: 1.4,
                visitsToLeadsConversionRate: 40.0,
                rows: [
                    new VisitAttributionFunnelReportRowView(
                        source: 'google',
                        medium: 'cpc',
                        campaign: 'spring-sale',
                        rawClicksCount: 8,
                        visitsCount: 5,
                        leadsCount: 2,
                        rawClicksPerVisitRate: 1.6,
                        visitsToLeadsConversionRate: 40.0,
                    ),
                    new VisitAttributionFunnelReportRowView(
                        source: 'google',
                        medium: 'cpc',
                        campaign: null,
                        rawClicksCount: 3,
                        visitsCount: 2,
                        leadsCount: 1,
                        rawClicksPerVisitRate: 1.5,
                        visitsToLeadsConversionRate: 50.0,
                    ),
                ],
            ));

        $this->app->instance(VisitAttributionFunnelReportReadModel::class, $readModel);

        $response = $this->get(route('admin.reports.visit-attribution-funnel', [
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ]));

        $response->assertOk();
        $response->assertSee('href="'.e(route('admin.clicks.index', [
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ])).'"', false);
        $response->assertSee('href="'.e(route('admin.visits.index', [
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ])).'"', false);
        $response->assertSee('href="'.e(route('admin.clicks.index', [
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
            'attributionSource' => 'google',
            'attributionMedium' => 'cpc',
            'attributionCampaign' => 'spring-sale',
        ])).'"', false);
        $response->assertSee('href="'.e(route('admin.visits.index', [
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
            'firstAttributionSource' => 'google',
            'firstAttributionMedium' => 'cpc',
            'firstAttributionCampaign' => 'spring-sale',
        ])).'"', false);
        $response->assertSee('href="'.e(route('admin.clicks.index', [
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
            'attributionSource' => 'google',
            'attributionMedium' => 'cpc',
            'attributionCampaignMissing' => '1',
        ])).'"', false);
        $response->assertSee('href="'.e(route('admin.visits.index', [
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
            'firstAttributionSource' => 'google',
            'firstAttributionMedium' => 'cpc',
            'firstAttributionCampaignMissing' => '1',
        ])).'"', false);
        $response->assertDontSee('href="'.e(route('admin.leads.index', [
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ])).'"', false);
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
}
