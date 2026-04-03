<?php

declare(strict_types=1);

namespace Tests\Unit\App\Providers\Inbound;

use Inbound\Application\Actions\Backoffice\AddLeadNote\AddLeadNoteAction;
use Inbound\Application\Actions\Backoffice\ChangeLeadStatus\ChangeLeadStatusAction;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\DashboardOverviewReadModel;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\GetDashboardOverviewHandler;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\FunnelTrendsReadModel;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\GetFunnelTrendsHandler;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\GetLeadDetailsHandler;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\LeadDetailsReadModel;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\GetLeadStatusReportHandler;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\LeadStatusReportReadModel;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\GetLeadTimelineHandler;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\LeadTimelineReadModel;
use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\GetOriginFunnelReportHandler;
use Inbound\Application\Queries\Backoffice\GetOriginFunnelReport\OriginFunnelReportReadModel;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\GetVisitAttributionFunnelReportHandler;
use Inbound\Application\Queries\Backoffice\GetVisitAttributionFunnelReport\VisitAttributionFunnelReportReadModel;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\GetVisitorAcquisitionFunnelReportHandler;
use Inbound\Application\Queries\Backoffice\GetVisitorAcquisitionFunnelReport\VisitorAcquisitionFunnelReportReadModel;
use Inbound\Application\Queries\Backoffice\ListClicks\ClicksListReadModel;
use Inbound\Application\Queries\Backoffice\ListClicks\ListClicksHandler;
use Inbound\Application\Queries\Backoffice\ListLeads\LeadsListReadModel;
use Inbound\Application\Queries\Backoffice\ListLeads\ListLeadsHandler;
use Inbound\Application\Queries\Backoffice\ListTouches\ListTouchesHandler;
use Inbound\Application\Queries\Backoffice\ListTouches\TouchesListReadModel;
use Inbound\Application\Queries\Backoffice\ListVisits\ListVisitsHandler;
use Inbound\Application\Queries\Backoffice\ListVisits\VisitsListReadModel;
use Inbound\Domain\LeadNote\LeadNoteRepository;
use Inbound\Domain\LeadStatusHistory\LeadStatusTransitionRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadNoteRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadStatusTransitionRepository;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentClicksListReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentDashboardOverviewReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentFunnelTrendsReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentLeadDetailsReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentLeadsListReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentLeadStatusReportReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentLeadTimelineReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentOriginFunnelReportReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentTouchesListReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentVisitAttributionFunnelReportReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentVisitorAcquisitionFunnelReportReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentVisitsListReadModel;
use Tests\TestCase;

final class BackofficeContainerWiringTest extends TestCase
{
    public function test_it_binds_backoffice_read_models_and_repositories_to_eloquent_implementations(): void
    {
        $this->assertInstanceOf(EloquentVisitAttributionFunnelReportReadModel::class, $this->app->make(VisitAttributionFunnelReportReadModel::class));
        $this->assertInstanceOf(EloquentDashboardOverviewReadModel::class, $this->app->make(DashboardOverviewReadModel::class));
        $this->assertInstanceOf(EloquentFunnelTrendsReadModel::class, $this->app->make(FunnelTrendsReadModel::class));
        $this->assertInstanceOf(EloquentClicksListReadModel::class, $this->app->make(ClicksListReadModel::class));
        $this->assertInstanceOf(EloquentLeadsListReadModel::class, $this->app->make(LeadsListReadModel::class));
        $this->assertInstanceOf(EloquentLeadDetailsReadModel::class, $this->app->make(LeadDetailsReadModel::class));
        $this->assertInstanceOf(EloquentLeadStatusReportReadModel::class, $this->app->make(LeadStatusReportReadModel::class));
        $this->assertInstanceOf(EloquentLeadTimelineReadModel::class, $this->app->make(LeadTimelineReadModel::class));
        $this->assertInstanceOf(EloquentOriginFunnelReportReadModel::class, $this->app->make(OriginFunnelReportReadModel::class));
        $this->assertInstanceOf(EloquentTouchesListReadModel::class, $this->app->make(TouchesListReadModel::class));
        $this->assertInstanceOf(EloquentVisitorAcquisitionFunnelReportReadModel::class, $this->app->make(VisitorAcquisitionFunnelReportReadModel::class));
        $this->assertInstanceOf(EloquentVisitsListReadModel::class, $this->app->make(VisitsListReadModel::class));
        $this->assertInstanceOf(EloquentLeadNoteRepository::class, $this->app->make(LeadNoteRepository::class));
        $this->assertInstanceOf(EloquentLeadStatusTransitionRepository::class, $this->app->make(LeadStatusTransitionRepository::class));
    }

    public function test_it_resolves_backoffice_use_cases_via_the_container(): void
    {
        $this->assertInstanceOf(GetVisitAttributionFunnelReportHandler::class, $this->app->make(GetVisitAttributionFunnelReportHandler::class));
        $this->assertInstanceOf(GetDashboardOverviewHandler::class, $this->app->make(GetDashboardOverviewHandler::class));
        $this->assertInstanceOf(GetFunnelTrendsHandler::class, $this->app->make(GetFunnelTrendsHandler::class));
        $this->assertInstanceOf(ListClicksHandler::class, $this->app->make(ListClicksHandler::class));
        $this->assertInstanceOf(ListLeadsHandler::class, $this->app->make(ListLeadsHandler::class));
        $this->assertInstanceOf(GetLeadDetailsHandler::class, $this->app->make(GetLeadDetailsHandler::class));
        $this->assertInstanceOf(GetLeadStatusReportHandler::class, $this->app->make(GetLeadStatusReportHandler::class));
        $this->assertInstanceOf(GetLeadTimelineHandler::class, $this->app->make(GetLeadTimelineHandler::class));
        $this->assertInstanceOf(GetOriginFunnelReportHandler::class, $this->app->make(GetOriginFunnelReportHandler::class));
        $this->assertInstanceOf(ListTouchesHandler::class, $this->app->make(ListTouchesHandler::class));
        $this->assertInstanceOf(GetVisitorAcquisitionFunnelReportHandler::class, $this->app->make(GetVisitorAcquisitionFunnelReportHandler::class));
        $this->assertInstanceOf(ListVisitsHandler::class, $this->app->make(ListVisitsHandler::class));
        $this->assertInstanceOf(AddLeadNoteAction::class, $this->app->make(AddLeadNoteAction::class));
        $this->assertInstanceOf(ChangeLeadStatusAction::class, $this->app->make(ChangeLeadStatusAction::class));
    }
}
