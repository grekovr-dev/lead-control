<?php

namespace App\Providers\Inbound;

use Illuminate\Support\ServiceProvider;
use Inbound\Application\Queries\Backoffice\ListClicks\ClicksListReadModel;
use Inbound\Application\Queries\Backoffice\ListTouches\TouchesListReadModel;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\DashboardOverviewReadModel;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\LeadDetailsReadModel;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\LeadStatusReportReadModel;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\LeadTimelineReadModel;
use Inbound\Application\Queries\Backoffice\ListLeads\LeadsListReadModel;
use Inbound\Application\Queries\Backoffice\ListVisits\VisitsListReadModel;
use Inbound\Domain\LeadNote\LeadNoteRepository;
use Inbound\Domain\LeadStatusHistory\LeadStatusTransitionRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadNoteRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadStatusTransitionRepository;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentClicksListReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentDashboardOverviewReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentLeadDetailsReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentLeadStatusReportReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentLeadTimelineReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentLeadsListReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentTouchesListReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentVisitsListReadModel;

class BackofficeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DashboardOverviewReadModel::class, EloquentDashboardOverviewReadModel::class);
        $this->app->bind(ClicksListReadModel::class, EloquentClicksListReadModel::class);
        $this->app->bind(LeadsListReadModel::class, EloquentLeadsListReadModel::class);
        $this->app->bind(LeadDetailsReadModel::class, EloquentLeadDetailsReadModel::class);
        $this->app->bind(LeadStatusReportReadModel::class, EloquentLeadStatusReportReadModel::class);
        $this->app->bind(LeadTimelineReadModel::class, EloquentLeadTimelineReadModel::class);
        $this->app->bind(TouchesListReadModel::class, EloquentTouchesListReadModel::class);
        $this->app->bind(VisitsListReadModel::class, EloquentVisitsListReadModel::class);

        $this->app->bind(LeadNoteRepository::class, EloquentLeadNoteRepository::class);
        $this->app->bind(LeadStatusTransitionRepository::class, EloquentLeadStatusTransitionRepository::class);
    }
}
