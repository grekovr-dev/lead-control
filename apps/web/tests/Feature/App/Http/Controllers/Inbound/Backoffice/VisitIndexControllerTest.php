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
                    && $query->firstAttributionCampaign === 'spring-sale'
                    && $query->lastAttributionSource === 'google'
                    && $query->lastAttributionMedium === 'organic'
                    && $query->startedAtRange?->fromInclusive()?->format('Y-m-d H:i:s') === '2026-03-01 00:00:00'
                    && $query->startedAtRange?->toExclusive()?->format('Y-m-d H:i:s') === '2026-04-01 00:00:00'
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
                        firstAttributionCampaign: 'spring-sale',
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
            'firstAttributionCampaign' => 'spring-sale',
            'lastAttributionSource' => 'google',
            'lastAttributionMedium' => 'organic',
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
            'page' => '2',
            'perPage' => '50',
        ]))
            ->assertOk()
            ->assertSeeText([
                'Візити',
                'Список візитів',
                'Контекст переходу',
                'ID відвідувача',
                'visitor-123',
                'Перше джерело',
                'Перший канал',
                'Перша кампанія',
                'Останнє джерело',
                'Останній канал',
                'Період візитів',
                '01.03.2026 - 31.03.2026',
                'google / cpc',
                'google / organic',
                'spring-sale',
                'Показано 51–51 із 61.',
            ])
            ->assertDontSee('name="visitorId"', false)
            ->assertDontSee('name="firstAttributionSource"', false)
            ->assertDontSee('name="firstAttributionMedium"', false)
            ->assertDontSee('name="lastAttributionSource"', false)
            ->assertDontSee('name="lastAttributionMedium"', false)
            ->assertDontSeeText('Застосувати')
            ->assertSee(route('admin.visits.index', [
                'visitorId' => 'visitor-123',
                'firstAttributionSource' => 'google',
                'firstAttributionMedium' => 'cpc',
                'firstAttributionCampaign' => 'spring-sale',
                'lastAttributionSource' => 'google',
                'lastAttributionMedium' => 'organic',
                'preset' => 'custom',
                'from' => '2026-03-01',
                'to' => '2026-03-31',
                'perPage' => 50,
                'page' => 1,
            ]));
    }

    public function test_it_renders_missing_visit_attribution_dimensions_from_drill_context(): void
    {
        $readModel = Mockery::mock(VisitsListReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::on(function (ListVisitsQuery $query): bool {
                return $query->firstAttributionSource === 'google'
                    && $query->firstAttributionSourceMissing === false
                    && $query->firstAttributionMedium === 'cpc'
                    && $query->firstAttributionMediumMissing === false
                    && $query->firstAttributionCampaign === null
                    && $query->firstAttributionCampaignMissing === true
                    && $query->startedAtRange?->fromInclusive()?->format('Y-m-d H:i:s') === '2026-03-01 00:00:00'
                    && $query->startedAtRange?->toExclusive()?->format('Y-m-d H:i:s') === '2026-04-01 00:00:00';
            }))
            ->andReturn(new VisitsListView(
                currentPage: 1,
                perPage: 20,
                total: 1,
                lastPage: 1,
                items: [
                    new VisitListItemView(
                        visitId: 'visit-1',
                        visitorId: 'visitor-123',
                        firstAttributionSource: 'google',
                        firstAttributionMedium: 'cpc',
                        firstAttributionCampaign: null,
                        lastAttributionSource: 'google',
                        lastAttributionMedium: 'organic',
                        startedAt: new DateTimeImmutable('2026-03-29 11:00:00'),
                        lastTouchedAt: new DateTimeImmutable('2026-03-29 12:05:00'),
                    ),
                ],
            ));

        $this->app->instance(VisitsListReadModel::class, $readModel);

        $this->get(route('admin.visits.index', [
            'firstAttributionSource' => 'google',
            'firstAttributionMedium' => 'cpc',
            'firstAttributionCampaignMissing' => '1',
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ]))
            ->assertOk()
            ->assertSeeText([
                'Перше джерело',
                'google',
                'Перший канал',
                'cpc',
                'Перша кампанія',
                'Без кампанії',
                'Період візитів',
                '01.03.2026 - 31.03.2026',
            ]);
    }
}
