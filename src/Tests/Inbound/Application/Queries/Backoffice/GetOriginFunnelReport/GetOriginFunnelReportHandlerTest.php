<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Queries\Backoffice\GetOriginFunnelReport;

use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\GetOriginFunnelReportHandler;
use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\GetOriginFunnelReportQuery;
use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\OriginFunnelReportReadModel;
use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\OriginFunnelReportRowView;
use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\OriginFunnelReportView;
use PHPUnit\Framework\TestCase;

final class GetOriginFunnelReportHandlerTest extends TestCase
{
    public function test_it_passes_the_query_to_the_read_model_and_returns_the_report_view(): void
    {
        $query = new GetOriginFunnelReportQuery();

        $expectedView = new OriginFunnelReportView(
            touchesCount: 8,
            leadsCount: 3,
            touchesToLeadsConversionRate: 37.5,
            rows: [
                new OriginFunnelReportRowView(
                    origin: 'form',
                    originLabel: 'Форма',
                    touchesCount: 4,
                    leadsCount: 2,
                    touchesToLeadsConversionRate: 50.0,
                ),
                new OriginFunnelReportRowView(
                    origin: 'phone_click',
                    originLabel: 'Клік по телефону',
                    touchesCount: 4,
                    leadsCount: 1,
                    touchesToLeadsConversionRate: 25.0,
                ),
            ],
        );

        $readModel = new RecordingOriginFunnelReportReadModel($expectedView);
        $handler = new GetOriginFunnelReportHandler($readModel);

        $result = $handler($query);

        $this->assertSame($query, $readModel->receivedQuery);
        $this->assertSame($expectedView, $result);
        $this->assertSame(8, $result->touchesCount);
        $this->assertSame(37.5, $result->touchesToLeadsConversionRate);
        $this->assertCount(2, $result->rows);
    }
}

final class RecordingOriginFunnelReportReadModel implements OriginFunnelReportReadModel
{
    public ?GetOriginFunnelReportQuery $receivedQuery = null;

    public function __construct(
        private readonly OriginFunnelReportView $view,
    ) {
    }

    public function __invoke(GetOriginFunnelReportQuery $query): OriginFunnelReportView
    {
        $this->receivedQuery = $query;

        return $this->view;
    }
}
