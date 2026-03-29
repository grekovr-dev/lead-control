<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Queries\Backoffice\GetAttributionFunnelReport;

use Inbound\Application\Queries\Backoffice\GetAttributionFunnelReport\AttributionFunnelReportReadModel;
use Inbound\Application\Queries\Backoffice\GetAttributionFunnelReport\AttributionFunnelReportRowView;
use Inbound\Application\Queries\Backoffice\GetAttributionFunnelReport\AttributionFunnelReportView;
use Inbound\Application\Queries\Backoffice\GetAttributionFunnelReport\GetAttributionFunnelReportHandler;
use Inbound\Application\Queries\Backoffice\GetAttributionFunnelReport\GetAttributionFunnelReportQuery;
use PHPUnit\Framework\TestCase;

final class GetAttributionFunnelReportHandlerTest extends TestCase
{
    public function test_it_passes_the_query_to_the_read_model_and_returns_the_report_view(): void
    {
        $query = new GetAttributionFunnelReportQuery();

        $expectedView = new AttributionFunnelReportView(
            rawClicksCount: 10,
            visitsCount: 8,
            leadsCount: 3,
            visitsToLeadsConversionRate: 37.5,
            rows: [
                new AttributionFunnelReportRowView(
                    source: 'google',
                    medium: 'cpc',
                    campaign: 'spring-sale',
                    rawClicksCount: 6,
                    visitsCount: 5,
                    leadsCount: 2,
                    visitsToLeadsConversionRate: 40.0,
                ),
                new AttributionFunnelReportRowView(
                    source: null,
                    medium: null,
                    campaign: null,
                    rawClicksCount: 4,
                    visitsCount: 3,
                    leadsCount: 1,
                    visitsToLeadsConversionRate: 33.33,
                ),
            ],
        );

        $readModel = new RecordingAttributionFunnelReportReadModel($expectedView);
        $handler = new GetAttributionFunnelReportHandler($readModel);

        $result = $handler($query);

        $this->assertSame($query, $readModel->receivedQuery);
        $this->assertSame($expectedView, $result);
        $this->assertSame(10, $result->rawClicksCount);
        $this->assertSame(37.5, $result->visitsToLeadsConversionRate);
        $this->assertCount(2, $result->rows);
    }
}

final class RecordingAttributionFunnelReportReadModel implements AttributionFunnelReportReadModel
{
    public ?GetAttributionFunnelReportQuery $receivedQuery = null;

    public function __construct(
        private readonly AttributionFunnelReportView $view,
    ) {
    }

    public function __invoke(GetAttributionFunnelReportQuery $query): AttributionFunnelReportView
    {
        $this->receivedQuery = $query;

        return $this->view;
    }
}
