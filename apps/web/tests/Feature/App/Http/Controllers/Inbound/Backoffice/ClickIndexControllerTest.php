<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Backoffice;

use DateTimeImmutable;
use Inbound\Application\Queries\Backoffice\ListClicks\ClickListItemView;
use Inbound\Application\Queries\Backoffice\ListClicks\ClicksListReadModel;
use Inbound\Application\Queries\Backoffice\ListClicks\ClicksListView;
use Inbound\Application\Queries\Backoffice\ListClicks\ListClicksQuery;
use Mockery;
use Tests\TestCase;

final class ClickIndexControllerTest extends TestCase
{
    public function test_it_renders_the_clicks_list_with_filters_and_paginated_rows(): void
    {
        $readModel = Mockery::mock(ClicksListReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::on(function (ListClicksQuery $query): bool {
                return $query->visitorId === 'visitor-123'
                    && $query->attributionSource === 'google'
                    && $query->attributionMedium === 'cpc'
                    && $query->attributionCampaign === 'spring-sale'
                    && $query->page === 2
                    && $query->perPage === 50;
            }))
            ->andReturn(new ClicksListView(
                currentPage: 2,
                perPage: 50,
                total: 73,
                lastPage: 2,
                items: [
                    new ClickListItemView(
                        clickId: 'click-2',
                        visitorId: 'visitor-123',
                        landingUrl: 'https://example.com/landing-b',
                        referrer: 'https://google.com/',
                        attributionSource: 'google',
                        attributionMedium: 'cpc',
                        attributionCampaign: 'spring-sale',
                        occurredAt: new DateTimeImmutable('2026-03-29 12:05:00'),
                    ),
                ],
            ));

        $this->app->instance(ClicksListReadModel::class, $readModel);

        $this->get(route('admin.clicks.index', [
            'visitorId' => 'visitor-123',
            'attributionSource' => 'google',
            'attributionMedium' => 'cpc',
            'attributionCampaign' => 'spring-sale',
            'page' => '2',
            'perPage' => '50',
        ]))
            ->assertOk()
            ->assertSeeText([
                'Кліки',
                'Список кліків',
                'visitor-123',
                'https://example.com/landing-b',
                'spring-sale',
                'Показано 51–51 із 73.',
            ])
            ->assertSee('value="visitor-123"', false)
            ->assertSee('value="google"', false)
            ->assertSee('value="cpc"', false)
            ->assertSee('value="spring-sale"', false)
            ->assertSee('option value="50" selected', false)
            ->assertSee(route('admin.clicks.index', [
                'visitorId' => 'visitor-123',
                'attributionSource' => 'google',
                'attributionMedium' => 'cpc',
                'attributionCampaign' => 'spring-sale',
                'perPage' => 50,
                'page' => 1,
            ]));
    }
}
