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
                    && $query->occurredAtRange?->fromInclusive()?->format('Y-m-d H:i:s') === '2026-03-01 00:00:00'
                    && $query->occurredAtRange?->toExclusive()?->format('Y-m-d H:i:s') === '2026-04-01 00:00:00'
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
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
            'page' => '2',
            'perPage' => '50',
        ]))
            ->assertOk()
            ->assertSeeText([
                'Кліки',
                'Список кліків',
                'Контекст переходу',
                'ID відвідувача',
                'visitor-123',
                'Джерело атрибуції',
                'Канал атрибуції',
                'Кампанія',
                'Період кліків',
                '01.03.2026 - 31.03.2026',
                'https://example.com/landing-b',
                'spring-sale',
                'Показано 51–51 із 73.',
            ])
            ->assertDontSee('name="visitorId"', false)
            ->assertDontSee('name="attributionSource"', false)
            ->assertDontSee('name="attributionMedium"', false)
            ->assertDontSee('name="attributionCampaign"', false)
            ->assertDontSeeText('Застосувати')
            ->assertSee(route('admin.clicks.index', [
                'visitorId' => 'visitor-123',
                'attributionSource' => 'google',
                'attributionMedium' => 'cpc',
                'attributionCampaign' => 'spring-sale',
                'preset' => 'custom',
                'from' => '2026-03-01',
                'to' => '2026-03-31',
                'perPage' => 50,
                'page' => 1,
            ]));
    }

    public function test_it_renders_missing_click_attribution_dimensions_from_drill_context(): void
    {
        $readModel = Mockery::mock(ClicksListReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::on(function (ListClicksQuery $query): bool {
                return $query->attributionSource === 'google'
                    && $query->attributionSourceMissing === false
                    && $query->attributionMedium === 'cpc'
                    && $query->attributionMediumMissing === false
                    && $query->attributionCampaign === null
                    && $query->attributionCampaignMissing === true
                    && $query->occurredAtRange?->fromInclusive()?->format('Y-m-d H:i:s') === '2026-03-01 00:00:00'
                    && $query->occurredAtRange?->toExclusive()?->format('Y-m-d H:i:s') === '2026-04-01 00:00:00';
            }))
            ->andReturn(new ClicksListView(
                currentPage: 1,
                perPage: 20,
                total: 1,
                lastPage: 1,
                items: [
                    new ClickListItemView(
                        clickId: 'click-1',
                        visitorId: 'visitor-123',
                        landingUrl: 'https://example.com/landing-a',
                        referrer: null,
                        attributionSource: 'google',
                        attributionMedium: 'cpc',
                        attributionCampaign: null,
                        occurredAt: new DateTimeImmutable('2026-03-29 12:05:00'),
                    ),
                ],
            ));

        $this->app->instance(ClicksListReadModel::class, $readModel);

        $this->get(route('admin.clicks.index', [
            'attributionSource' => 'google',
            'attributionMedium' => 'cpc',
            'attributionCampaignMissing' => '1',
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ]))
            ->assertOk()
            ->assertSeeText([
                'Джерело атрибуції',
                'google',
                'Канал атрибуції',
                'cpc',
                'Кампанія',
                'Без кампанії',
                'Період кліків',
                '01.03.2026 - 31.03.2026',
            ]);
    }
}
