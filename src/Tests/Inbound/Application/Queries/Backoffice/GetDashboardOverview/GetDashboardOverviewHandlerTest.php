<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Queries\Backoffice\GetDashboardOverview;

use DateTimeImmutable;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\DashboardBreakdownItemView;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\DashboardOverviewReadModel;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\DashboardOverviewView;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\DashboardRecentLeadView;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\GetDashboardOverviewHandler;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\GetDashboardOverviewQuery;
use PHPUnit\Framework\TestCase;

final class GetDashboardOverviewHandlerTest extends TestCase
{
    public function test_it_passes_the_query_to_the_read_model_and_returns_its_view(): void
    {
        $query = new GetDashboardOverviewQuery();
        $expectedView = new DashboardOverviewView(
            clicksCount: 120,
            visitsCount: 80,
            touchesCount: 45,
            leadsCount: 15,
            clicksToLeadsConversionRate: 12.5,
            visitsToLeadsConversionRate: 18.75,
            leadStatusBreakdown: [
                new DashboardBreakdownItemView('new', 'Новый', 10),
                new DashboardBreakdownItemView('won', 'Выигран', 5),
            ],
            touchTypeBreakdown: [
                new DashboardBreakdownItemView('lead_form_click', 'Клік по формі', 20),
                new DashboardBreakdownItemView('phone_click', 'Клік по телефону', 25),
            ],
            leadOriginBreakdown: [
                new DashboardBreakdownItemView('form', 'Форма', 9),
                new DashboardBreakdownItemView('phone_click', 'Клік по телефону', 6),
            ],
            recentLeads: [
                new DashboardRecentLeadView(
                    leadId: 'lead-1',
                    visitorId: 'visitor-1',
                    visitId: 'visit-1',
                    name: 'John Doe',
                    phone: '+380501112233',
                    status: 'new',
                    statusLabel: 'Новый',
                    origin: 'form',
                    attributionSource: 'google',
                    attributionMedium: 'cpc',
                    createdAt: new DateTimeImmutable('2026-03-26T10:00:00+02:00'),
                ),
            ],
        );

        $readModel = new RecordingDashboardOverviewReadModel($expectedView);
        $handler = new GetDashboardOverviewHandler($readModel);

        $result = $handler($query);

        $this->assertSame($query, $readModel->receivedQuery);
        $this->assertSame($expectedView, $result);
        $this->assertSame(120, $result->clicksCount);
        $this->assertSame(12.5, $result->clicksToLeadsConversionRate);
        $this->assertCount(2, $result->leadStatusBreakdown);
        $this->assertCount(1, $result->recentLeads);
    }
}

final class RecordingDashboardOverviewReadModel implements DashboardOverviewReadModel
{
    public ?GetDashboardOverviewQuery $receivedQuery = null;

    public function __construct(
        private readonly DashboardOverviewView $view,
    ) {
    }

    public function __invoke(GetDashboardOverviewQuery $query): DashboardOverviewView
    {
        $this->receivedQuery = $query;

        return $this->view;
    }
}
