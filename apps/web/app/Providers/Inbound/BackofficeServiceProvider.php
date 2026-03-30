<?php

namespace App\Providers\Inbound;

use Illuminate\Support\ServiceProvider;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\DashboardOverviewReadModel;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\LeadDetailsReadModel;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\LeadTimelineReadModel;
use Inbound\Application\Queries\Backoffice\ListLeads\LeadsListReadModel;
use Inbound\Domain\LeadNote\LeadNoteRepository;
use Inbound\Domain\LeadStatusHistory\LeadStatusTransitionRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadNoteRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadStatusTransitionRepository;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentDashboardOverviewReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentLeadDetailsReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentLeadTimelineReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentLeadsListReadModel;

class BackofficeServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(DashboardOverviewReadModel::class, EloquentDashboardOverviewReadModel::class);
        $this->app->bind(LeadsListReadModel::class, EloquentLeadsListReadModel::class);
        $this->app->bind(LeadDetailsReadModel::class, EloquentLeadDetailsReadModel::class);
        $this->app->bind(LeadTimelineReadModel::class, EloquentLeadTimelineReadModel::class);

        $this->app->bind(LeadNoteRepository::class, EloquentLeadNoteRepository::class);
        $this->app->bind(LeadStatusTransitionRepository::class, EloquentLeadStatusTransitionRepository::class);
    }
}
