<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Queries\Backoffice\GetLeadStatusReport;

use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\GetLeadStatusReportHandler;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\GetLeadStatusReportQuery;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\LeadStatusReportReadModel;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\LeadStatusReportRowView;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\LeadStatusReportView;
use PHPUnit\Framework\TestCase;

final class GetLeadStatusReportHandlerTest extends TestCase
{
    public function test_it_passes_the_query_to_the_read_model_and_returns_the_report_view(): void
    {
        $query = new GetLeadStatusReportQuery();

        $expectedView = new LeadStatusReportView(
            leadsCount: 8,
            rows: [
                new LeadStatusReportRowView(
                    status: 'new',
                    statusLabel: 'Новий',
                    leadsCount: 3,
                    shareOfTotalRate: 37.5,
                ),
                new LeadStatusReportRowView(
                    status: 'won',
                    statusLabel: 'Успішний',
                    leadsCount: 2,
                    shareOfTotalRate: 25.0,
                ),
            ],
        );

        $readModel = new RecordingLeadStatusReportReadModel($expectedView);
        $handler = new GetLeadStatusReportHandler($readModel);

        $result = $handler($query);

        $this->assertSame($query, $readModel->receivedQuery);
        $this->assertSame($expectedView, $result);
        $this->assertSame(8, $result->leadsCount);
        $this->assertCount(2, $result->rows);
        $this->assertSame(37.5, $result->rows[0]->shareOfTotalRate);
    }
}

final class RecordingLeadStatusReportReadModel implements LeadStatusReportReadModel
{
    public ?GetLeadStatusReportQuery $receivedQuery = null;

    public function __construct(
        private readonly LeadStatusReportView $view,
    ) {
    }

    public function __invoke(GetLeadStatusReportQuery $query): LeadStatusReportView
    {
        $this->receivedQuery = $query;

        return $this->view;
    }
}
