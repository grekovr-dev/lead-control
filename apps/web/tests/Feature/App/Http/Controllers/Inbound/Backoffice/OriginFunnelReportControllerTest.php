<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Backoffice;

use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\GetOriginFunnelReportQuery;
use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\OriginFunnelReportReadModel;
use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\OriginFunnelReportRowView;
use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\OriginFunnelReportView;
use Mockery;
use Tests\TestCase;

final class OriginFunnelReportControllerTest extends TestCase
{
    public function test_it_renders_the_origin_funnel_report_screen(): void
    {
        $readModel = Mockery::mock(OriginFunnelReportReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::type(GetOriginFunnelReportQuery::class))
            ->andReturn(new OriginFunnelReportView(
                touchesCount: 12,
                leadsCount: 5,
                touchesToLeadsConversionRate: 41.67,
                rows: [
                    new OriginFunnelReportRowView(
                        origin: 'form',
                        originLabel: 'Форма',
                        touchesCount: 7,
                        leadsCount: 3,
                        touchesToLeadsConversionRate: 42.86,
                        touchDrillType: 'lead_form_click',
                    ),
                    new OriginFunnelReportRowView(
                        origin: 'phone_click',
                        originLabel: 'Клік по телефону',
                        touchesCount: 5,
                        leadsCount: 2,
                        touchesToLeadsConversionRate: 40.0,
                        touchDrillType: 'phone_click',
                    ),
                ],
            ));

        $this->app->instance(OriginFunnelReportReadModel::class, $readModel);

        $response = $this->get(route('admin.reports.origin-funnel'));

        $response
            ->assertOk()
            ->assertViewIs('admin.reports.origin-funnel')
            ->assertSeeText([
                'Воронка за походженням',
                'Звіт за походженням',
                'Загальна конверсія',
                'Усього мапованих дотиків',
                'Усього лідів',
                'Походження',
                'Дотиків',
                'Лідів',
                'Конверсія дотиків у ліди',
                'Форма',
                'Клік по телефону',
                '41,67%',
                '42,86%',
                '40,00%',
            ]);
        $content = $response->getContent();

        self::assertIsString($content);
        $this->assertStringContainsString(
            route('admin.touches.index', ['type' => 'lead_form_click']),
            $content,
        );
        $this->assertStringContainsString(
            route('admin.touches.index', ['type' => 'phone_click']),
            $content,
        );
    }

    public function test_it_renders_plain_touch_counts_when_a_row_has_no_touch_drill_type(): void
    {
        $readModel = Mockery::mock(OriginFunnelReportReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::type(GetOriginFunnelReportQuery::class))
            ->andReturn(new OriginFunnelReportView(
                touchesCount: 3,
                leadsCount: 2,
                touchesToLeadsConversionRate: 66.67,
                rows: [
                    new OriginFunnelReportRowView(
                        origin: 'legacy_import',
                        originLabel: 'Імпорт',
                        touchesCount: 0,
                        leadsCount: 2,
                        touchesToLeadsConversionRate: 0.0,
                        touchDrillType: null,
                    ),
                ],
            ));

        $this->app->instance(OriginFunnelReportReadModel::class, $readModel);

        $response = $this->get(route('admin.reports.origin-funnel'));

        $response
            ->assertOk()
            ->assertSeeText([
                'Імпорт',
                '0',
                '2',
            ]);

        $content = $response->getContent();

        self::assertIsString($content);
        $this->assertStringNotContainsString(
            route('admin.touches.index', ['type' => 'legacy_import']),
            $content,
        );
    }
}
