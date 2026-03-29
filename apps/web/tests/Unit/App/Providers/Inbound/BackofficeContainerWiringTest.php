<?php

declare(strict_types=1);

namespace Tests\Unit\App\Providers\Inbound;

use Inbound\Application\Actions\Backoffice\AddLeadNote\AddLeadNoteAction;
use Inbound\Application\Actions\Backoffice\ChangeLeadStatus\ChangeLeadStatusAction;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\DashboardOverviewReadModel;
use Inbound\Application\Queries\Backoffice\GetDashboardOverview\GetDashboardOverviewHandler;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\GetLeadDetailsHandler;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\LeadDetailsReadModel;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\GetLeadTimelineHandler;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\LeadTimelineReadModel;
use Inbound\Application\Queries\Backoffice\ListLeads\LeadsListReadModel;
use Inbound\Application\Queries\Backoffice\ListLeads\ListLeadsHandler;
use Inbound\Domain\LeadNote\LeadNoteRepository;
use Inbound\Domain\LeadStatusHistory\LeadStatusTransitionRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadNoteRepository;
use Inbound\Infrastructure\Persistence\Eloquent\EloquentLeadStatusTransitionRepository;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentDashboardOverviewReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentLeadDetailsReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentLeadTimelineReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentLeadsListReadModel;
use Tests\TestCase;

final class BackofficeContainerWiringTest extends TestCase
{
    public function test_it_binds_backoffice_read_models_and_repositories_to_eloquent_implementations(): void
    {
        $this->assertInstanceOf(EloquentDashboardOverviewReadModel::class, $this->app->make(DashboardOverviewReadModel::class));
        $this->assertInstanceOf(EloquentLeadsListReadModel::class, $this->app->make(LeadsListReadModel::class));
        $this->assertInstanceOf(EloquentLeadDetailsReadModel::class, $this->app->make(LeadDetailsReadModel::class));
        $this->assertInstanceOf(EloquentLeadTimelineReadModel::class, $this->app->make(LeadTimelineReadModel::class));
        $this->assertInstanceOf(EloquentLeadNoteRepository::class, $this->app->make(LeadNoteRepository::class));
        $this->assertInstanceOf(EloquentLeadStatusTransitionRepository::class, $this->app->make(LeadStatusTransitionRepository::class));
    }

    public function test_it_resolves_first_wave_backoffice_use_cases_via_the_container(): void
    {
        $this->assertInstanceOf(GetDashboardOverviewHandler::class, $this->app->make(GetDashboardOverviewHandler::class));
        $this->assertInstanceOf(ListLeadsHandler::class, $this->app->make(ListLeadsHandler::class));
        $this->assertInstanceOf(GetLeadDetailsHandler::class, $this->app->make(GetLeadDetailsHandler::class));
        $this->assertInstanceOf(GetLeadTimelineHandler::class, $this->app->make(GetLeadTimelineHandler::class));
        $this->assertInstanceOf(AddLeadNoteAction::class, $this->app->make(AddLeadNoteAction::class));
        $this->assertInstanceOf(ChangeLeadStatusAction::class, $this->app->make(ChangeLeadStatusAction::class));
    }
}
