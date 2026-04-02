<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport;

use DateTimeImmutable;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\GetVisitorAcquisitionFunnelReportHandler;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\GetVisitorAcquisitionFunnelReportQuery;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\VisitorAcquisitionFunnelReportReadModel;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\VisitorAcquisitionFunnelReportRowView;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\VisitorAcquisitionFunnelReportView;
use Inbound\Domain\Shared\DateRange;
use PHPUnit\Framework\TestCase;

final class GetVisitorAcquisitionFunnelReportHandlerTest extends TestCase
{
    public function test_it_passes_the_query_to_the_read_model_and_returns_the_report_view(): void
    {
        $query = new GetVisitorAcquisitionFunnelReportQuery(
            firstVisitPeriod: new DateRange(
                new DateTimeImmutable('2026-03-01 00:00:00'),
                new DateTimeImmutable('2026-04-01 00:00:00'),
            ),
        );

        $expectedView = new VisitorAcquisitionFunnelReportView(
            visitorsCount: 9,
            leadsCount: 3,
            visitorsToLeadsConversionRate: 33.33,
            rows: [
                new VisitorAcquisitionFunnelReportRowView(
                    visitorAttributionSource: 'google',
                    visitorAttributionMedium: 'cpc',
                    visitorAttributionCampaign: 'spring-sale',
                    visitorsCount: 5,
                    leadsCount: 2,
                    visitorsToLeadsConversionRate: 40.0,
                ),
                new VisitorAcquisitionFunnelReportRowView(
                    visitorAttributionSource: null,
                    visitorAttributionMedium: null,
                    visitorAttributionCampaign: null,
                    visitorsCount: 4,
                    leadsCount: 1,
                    visitorsToLeadsConversionRate: 25.0,
                ),
            ],
        );

        $readModel = new RecordingVisitorAcquisitionFunnelReportReadModel($expectedView);
        $handler = new GetVisitorAcquisitionFunnelReportHandler($readModel);

        $result = $handler($query);

        $this->assertSame($query, $readModel->receivedQuery);
        $this->assertSame($expectedView, $result);
        $this->assertNotNull($readModel->receivedQuery?->firstVisitPeriod);
        $this->assertSame('2026-03-01 00:00:00', $readModel->receivedQuery?->firstVisitPeriod?->fromInclusive()?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-04-01 00:00:00', $readModel->receivedQuery?->firstVisitPeriod?->toExclusive()?->format('Y-m-d H:i:s'));
        $this->assertSame(9, $result->visitorsCount);
        $this->assertSame(33.33, $result->visitorsToLeadsConversionRate);
        $this->assertCount(2, $result->rows);
    }
}

final class RecordingVisitorAcquisitionFunnelReportReadModel implements VisitorAcquisitionFunnelReportReadModel
{
    public ?GetVisitorAcquisitionFunnelReportQuery $receivedQuery = null;

    public function __construct(
        private readonly VisitorAcquisitionFunnelReportView $view,
    ) {
    }

    public function __invoke(GetVisitorAcquisitionFunnelReportQuery $query): VisitorAcquisitionFunnelReportView
    {
        $this->receivedQuery = $query;

        return $this->view;
    }
}
