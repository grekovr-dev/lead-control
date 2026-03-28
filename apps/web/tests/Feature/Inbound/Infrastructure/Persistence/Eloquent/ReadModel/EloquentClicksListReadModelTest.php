<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\ListClicks\ListClicksQuery;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentClicksListReadModel;
use Tests\TestCase;

final class EloquentClicksListReadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_paginated_clicks_filtered_by_visitor_and_attribution(): void
    {
        $this->createClick(
            id: 'click-1',
            visitorId: 'visitor-123',
            landingUrl: 'https://example.com/landing-1',
            occurredAt: '2026-03-28 11:00:00',
            referrer: null,
            attributionSource: 'google',
            attributionMedium: 'cpc',
            attributionCampaign: 'spring-sale',
        );

        $this->createClick(
            id: 'click-2',
            visitorId: 'visitor-123',
            landingUrl: 'https://example.com/landing-2',
            occurredAt: '2026-03-28 11:10:00',
            referrer: 'https://google.com/',
            attributionSource: 'google',
            attributionMedium: 'cpc',
            attributionCampaign: 'spring-sale',
        );

        $this->createClick(
            id: 'click-3',
            visitorId: 'visitor-123',
            landingUrl: 'https://example.com/landing-3',
            occurredAt: '2026-03-28 11:20:00',
            referrer: null,
            attributionSource: 'google',
            attributionMedium: 'organic',
            attributionCampaign: 'spring-sale',
        );

        $this->createClick(
            id: 'click-4',
            visitorId: 'visitor-999',
            landingUrl: 'https://example.com/landing-4',
            occurredAt: '2026-03-28 11:30:00',
            referrer: null,
            attributionSource: 'google',
            attributionMedium: 'cpc',
            attributionCampaign: 'spring-sale',
        );

        $readModel = new EloquentClicksListReadModel();

        $view = $readModel(new ListClicksQuery(
            visitorId: 'visitor-123',
            attributionSource: 'google',
            attributionMedium: 'cpc',
            attributionCampaign: 'spring-sale',
            page: 1,
            perPage: 1,
        ));

        $this->assertSame(1, $view->currentPage);
        $this->assertSame(1, $view->perPage);
        $this->assertSame(2, $view->total);
        $this->assertSame(2, $view->lastPage);
        $this->assertCount(1, $view->items);
        $this->assertSame('click-2', $view->items[0]->clickId);
        $this->assertSame('visitor-123', $view->items[0]->visitorId);
        $this->assertSame('https://google.com/', $view->items[0]->referrer);
        $this->assertSame('spring-sale', $view->items[0]->attributionCampaign);
    }

    public function test_it_returns_unfiltered_clicks_in_reverse_chronological_order(): void
    {
        $this->createClick(
            id: 'click-1',
            visitorId: 'visitor-1',
            landingUrl: 'https://example.com/landing-1',
            occurredAt: '2026-03-28 11:00:00',
            referrer: null,
            attributionSource: null,
            attributionMedium: null,
            attributionCampaign: null,
        );

        $this->createClick(
            id: 'click-2',
            visitorId: 'visitor-2',
            landingUrl: 'https://example.com/landing-2',
            occurredAt: '2026-03-28 11:05:00',
            referrer: 'https://facebook.com/',
            attributionSource: 'facebook',
            attributionMedium: 'paid-social',
            attributionCampaign: 'lookalike',
        );

        $readModel = new EloquentClicksListReadModel();

        $view = $readModel(new ListClicksQuery());

        $this->assertSame(2, $view->total);
        $this->assertSame(1, $view->lastPage);
        $this->assertCount(2, $view->items);
        $this->assertSame(['click-2', 'click-1'], array_map(
            static fn ($item): string => $item->clickId,
            $view->items,
        ));
        $this->assertSame('facebook', $view->items[0]->attributionSource);
        $this->assertNull($view->items[1]->attributionSource);
    }

    private function createClick(
        string $id,
        string $visitorId,
        string $landingUrl,
        string $occurredAt,
        ?string $referrer,
        ?string $attributionSource,
        ?string $attributionMedium,
        ?string $attributionCampaign,
    ): void {
        ClickModel::query()->create([
            'id' => $id,
            'visitor_id' => $visitorId,
            'landing_url' => $landingUrl,
            'referrer' => $referrer,
            'occurred_at' => $occurredAt,
            'attribution_source' => $attributionSource,
            'attribution_medium' => $attributionMedium,
            'attribution_campaign' => $attributionCampaign,
        ]);
    }
}
