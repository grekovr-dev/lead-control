<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Backoffice;

use DateTimeImmutable;
use Inbound\Application\Queries\Backoffice\ListVisits\ListVisitsQuery;
use Inbound\Application\Queries\Backoffice\ListVisits\VisitListItemView;
use Inbound\Application\Queries\Backoffice\ListVisits\VisitsListReadModel;
use Inbound\Application\Queries\Backoffice\ListVisits\VisitsListView;
use Mockery;
use Tests\TestCase;

final class VisitIndexControllerTest extends TestCase
{
    public function test_it_renders_the_visits_list_with_filters_and_paginated_rows(): void
    {
        $readModel = Mockery::mock(VisitsListReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::on(function (ListVisitsQuery $query): bool {
                return $query->visitorId === 'visitor-123'
                    && $query->firstAttributionSource === 'google'
                    && $query->firstAttributionMedium === 'cpc'
                    && $query->lastAttributionSource === 'google'
                    && $query->lastAttributionMedium === 'organic'
                    && $query->page === 2
                    && $query->perPage === 50;
            }))
            ->andReturn(new VisitsListView(
                currentPage: 2,
                perPage: 50,
                total: 61,
                lastPage: 2,
                items: [
                    new VisitListItemView(
                        visitId: 'visit-2',
                        visitorId: 'visitor-123',
                        firstAttributionSource: 'google',
                        firstAttributionMedium: 'cpc',
                        lastAttributionSource: 'google',
                        lastAttributionMedium: 'organic',
                        startedAt: new DateTimeImmutable('2026-03-29 11:00:00'),
                        lastTouchedAt: new DateTimeImmutable('2026-03-29 12:05:00'),
                    ),
                ],
            ));

        $this->app->instance(VisitsListReadModel::class, $readModel);

        $this->get(route('admin.visits.index', [
            'visitorId' => 'visitor-123',
            'firstAttributionSource' => 'google',
            'firstAttributionMedium' => 'cpc',
            'lastAttributionSource' => 'google',
            'lastAttributionMedium' => 'organic',
            'page' => '2',
            'perPage' => '50',
        ]))
            ->assertOk()
            ->assertSeeText([
                'Візити',
                'Список візитів',
                'visitor-123',
                'google / cpc',
                'google / organic',
                'Показано 51–51 із 61.',
            ])
            ->assertSee('value="visitor-123"', false)
            ->assertSee('value="google"', false)
            ->assertSee('value="cpc"', false)
            ->assertSee('value="organic"', false)
            ->assertSee('option value="50" selected', false)
            ->assertSee(route('admin.visits.index', [
                'visitorId' => 'visitor-123',
                'firstAttributionSource' => 'google',
                'firstAttributionMedium' => 'cpc',
                'lastAttributionSource' => 'google',
                'lastAttributionMedium' => 'organic',
                'perPage' => 50,
                'page' => 1,
            ]));
    }
}
