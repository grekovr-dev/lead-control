<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Backoffice;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\FunnelTrendPointView;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\FunnelTrendsReadModel;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\FunnelTrendsView;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\GetFunnelTrendsQuery;
use Mockery;
use Tests\TestCase;

final class FunnelTrendsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_funnel_trends_screen(): void
    {
        $readModel = Mockery::mock(FunnelTrendsReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::type(GetFunnelTrendsQuery::class))
            ->andReturn(new FunnelTrendsView(
                dateFrom: null,
                dateTo: null,
                clicksCount: 120,
                visitsCount: 80,
                leadsCount: 18,
                clicksToLeadsConversionRate: 15.0,
                visitsToLeadsConversionRate: 22.5,
                rows: [
                    new FunnelTrendPointView(
                        date: new DateTimeImmutable('2026-03-29 00:00:00'),
                        clicksCount: 40,
                        visitsCount: 24,
                        leadsCount: 6,
                        clicksToLeadsConversionRate: 15.0,
                        visitsToLeadsConversionRate: 25.0,
                    ),
                    new FunnelTrendPointView(
                        date: new DateTimeImmutable('2026-03-30 00:00:00'),
                        clicksCount: 80,
                        visitsCount: 56,
                        leadsCount: 12,
                        clicksToLeadsConversionRate: 15.0,
                        visitsToLeadsConversionRate: 21.43,
                    ),
                ],
            ));

        $this->app->instance(FunnelTrendsReadModel::class, $readModel);

        $this->get(route('admin.reports.funnel-trends'))
            ->assertOk()
            ->assertViewIs('admin.reports.funnel-trends')
            ->assertSeeText([
                'Динаміка воронки',
                'Денний тренд воронки',
                'Пресет',
                'Період звіту',
                'Сирі кліки',
                'Візити',
                'Ліди',
                'Співвідношення кліків до лідів',
                'Конверсія візитів у ліди',
                '29.03.2026',
                '30.03.2026',
                '25,00%',
                '21,43%',
            ]);
    }

    public function test_it_passes_the_selected_period_to_the_handler(): void
    {
        $readModel = Mockery::mock(FunnelTrendsReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::on(function (GetFunnelTrendsQuery $query): bool {
                return $query->dateFrom?->format('Y-m-d H:i:s') === '2026-03-01 00:00:00'
                    && $query->dateTo?->format('Y-m-d H:i:s') === '2026-03-31 00:00:00';
            }))
            ->andReturn(new FunnelTrendsView(
                dateFrom: new DateTimeImmutable('2026-03-01 00:00:00'),
                dateTo: new DateTimeImmutable('2026-03-31 00:00:00'),
                clicksCount: 0,
                visitsCount: 0,
                leadsCount: 0,
                clicksToLeadsConversionRate: 0.0,
                visitsToLeadsConversionRate: 0.0,
                rows: [],
            ));

        $this->app->instance(FunnelTrendsReadModel::class, $readModel);

        $response = $this->get(route('admin.reports.funnel-trends', [
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
        $response->assertSeeText('Для динаміки воронки поки немає даних.');
    }
}
