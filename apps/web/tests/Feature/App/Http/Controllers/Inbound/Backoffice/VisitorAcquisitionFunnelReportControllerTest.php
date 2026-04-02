<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Backoffice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\GetVisitorAcquisitionFunnelReportQuery;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\VisitorAcquisitionFunnelReportReadModel;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\VisitorAcquisitionFunnelReportRowView;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\VisitorAcquisitionFunnelReportView;
use Mockery;
use Tests\TestCase;

final class VisitorAcquisitionFunnelReportControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_visitor_acquisition_funnel_report_screen(): void
    {
        $readModel = Mockery::mock(VisitorAcquisitionFunnelReportReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::type(GetVisitorAcquisitionFunnelReportQuery::class))
            ->andReturn(new VisitorAcquisitionFunnelReportView(
                visitorsCount: 12,
                leadsCount: 3,
                visitorsToLeadsConversionRate: 25.0,
                rows: [
                    new VisitorAcquisitionFunnelReportRowView(
                        visitorAttributionSource: 'google',
                        visitorAttributionMedium: 'cpc',
                        visitorAttributionCampaign: 'spring-sale',
                        visitorsCount: 7,
                        leadsCount: 2,
                        visitorsToLeadsConversionRate: 28.57,
                    ),
                    new VisitorAcquisitionFunnelReportRowView(
                        visitorAttributionSource: null,
                        visitorAttributionMedium: null,
                        visitorAttributionCampaign: null,
                        visitorsCount: 5,
                        leadsCount: 1,
                        visitorsToLeadsConversionRate: 20.0,
                    ),
                ],
            ));

        $this->app->instance(VisitorAcquisitionFunnelReportReadModel::class, $readModel);

        $this->get(route('admin.reports.visitor-acquisition-funnel'))
            ->assertOk()
            ->assertViewIs('admin.reports.visitor-acquisition-funnel')
            ->assertSeeText([
                'Воронка залучення відвідувачів',
                'Когорти першого залучення',
                'На відміну від воронки атрибуції візитів',
                'Пресет',
                'Період першого візиту',
                'Нові відвідувачі',
                'Ліди',
                'Конверсія відвідувачів у ліди',
                'Основний KPI',
                'google',
                'cpc',
                'spring-sale',
                'Без атрибуції',
                'Без кампанії',
                '25,00%',
                '28,57%',
                '20,00%',
            ]);
    }

    public function test_it_passes_the_selected_period_to_the_handler(): void
    {
        $readModel = Mockery::mock(VisitorAcquisitionFunnelReportReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::on(function (GetVisitorAcquisitionFunnelReportQuery $query): bool {
                return $query->firstVisitPeriod?->fromInclusive()?->format('Y-m-d H:i:s') === '2026-03-01 00:00:00'
                    && $query->firstVisitPeriod?->toExclusive()?->format('Y-m-d H:i:s') === '2026-04-01 00:00:00';
            }))
            ->andReturn(new VisitorAcquisitionFunnelReportView(
                visitorsCount: 0,
                leadsCount: 0,
                visitorsToLeadsConversionRate: 0.0,
                rows: [],
            ));

        $this->app->instance(VisitorAcquisitionFunnelReportReadModel::class, $readModel);

        $response = $this->get(route('admin.reports.visitor-acquisition-funnel', [
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ]));

        $response->assertOk();
        $response->assertViewHas('filters', fn (array $filters): bool => $filters === [
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ]);
        $response->assertSee('option value="custom" selected', false);
        $response->assertSee('value="2026-03-01"', false);
        $response->assertSee('value="2026-03-31"', false);
        $response->assertSeeText('Для воронки залучення відвідувачів поки немає даних.');
    }
}
