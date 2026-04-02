<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport;

use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\GetVisitAttributionFunnelReportHandler;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\GetVisitAttributionFunnelReportQuery;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\VisitAttributionFunnelReportReadModel;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\VisitAttributionFunnelReportRowView;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\VisitAttributionFunnelReportView;
use PHPUnit\Framework\TestCase;

final class GetVisitAttributionFunnelReportHandlerTest extends TestCase
{
    public function test_it_passes_the_query_to_the_read_model_and_returns_the_report_view(): void
    {
        $query = new GetVisitAttributionFunnelReportQuery();

        $expectedView = new VisitAttributionFunnelReportView(
            rawClicksCount: 10,
            visitsCount: 8,
            leadsCount: 3,
            rawClicksPerVisitRate: 1.25,
            visitsToLeadsConversionRate: 37.5,
            rows: [
                new VisitAttributionFunnelReportRowView(
                    source: 'google',
                    medium: 'cpc',
                    campaign: 'spring-sale',
                    rawClicksCount: 6,
                    visitsCount: 5,
                    leadsCount: 2,
                    rawClicksPerVisitRate: 1.2,
                    visitsToLeadsConversionRate: 40.0,
                ),
                new VisitAttributionFunnelReportRowView(
                    source: null,
                    medium: null,
                    campaign: null,
                    rawClicksCount: 4,
                    visitsCount: 3,
                    leadsCount: 1,
                    rawClicksPerVisitRate: 1.33,
                    visitsToLeadsConversionRate: 33.33,
                ),
            ],
        );

        $readModel = new RecordingVisitAttributionFunnelReportReadModel($expectedView);
        $handler = new GetVisitAttributionFunnelReportHandler($readModel);

        $result = $handler($query);

        $this->assertSame($query, $readModel->receivedQuery);
        $this->assertSame($expectedView, $result);
        $this->assertSame(10, $result->rawClicksCount);
        $this->assertSame(1.25, $result->rawClicksPerVisitRate);
        $this->assertSame(37.5, $result->visitsToLeadsConversionRate);
        $this->assertCount(2, $result->rows);
    }
}

final class RecordingVisitAttributionFunnelReportReadModel implements VisitAttributionFunnelReportReadModel
{
    public ?GetVisitAttributionFunnelReportQuery $receivedQuery = null;

    public function __construct(
        private readonly VisitAttributionFunnelReportView $view,
    ) {
    }

    public function __invoke(GetVisitAttributionFunnelReportQuery $query): VisitAttributionFunnelReportView
    {
        $this->receivedQuery = $query;

        return $this->view;
    }
}
