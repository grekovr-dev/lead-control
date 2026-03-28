<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Queries\Backoffice\ListClicks;

use DateTimeImmutable;
use Inbound\Application\Queries\Backoffice\ListClicks\ClickListItemView;
use Inbound\Application\Queries\Backoffice\ListClicks\ClicksListReadModel;
use Inbound\Application\Queries\Backoffice\ListClicks\ClicksListView;
use Inbound\Application\Queries\Backoffice\ListClicks\ListClicksHandler;
use Inbound\Application\Queries\Backoffice\ListClicks\ListClicksQuery;
use PHPUnit\Framework\TestCase;

final class ListClicksHandlerTest extends TestCase
{
    public function test_it_passes_the_query_to_the_read_model_and_returns_a_paginated_view(): void
    {
        $query = new ListClicksQuery(
            visitorId: 'visitor-123',
            attributionSource: 'google',
            attributionMedium: 'cpc',
            attributionCampaign: 'spring-sale',
            page: 2,
            perPage: 10,
        );

        $expectedView = new ClicksListView(
            currentPage: 2,
            perPage: 10,
            total: 14,
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
                    occurredAt: new DateTimeImmutable('2026-03-28T12:05:00+02:00'),
                ),
                new ClickListItemView(
                    clickId: 'click-1',
                    visitorId: 'visitor-123',
                    landingUrl: 'https://example.com/landing-a',
                    referrer: null,
                    attributionSource: 'google',
                    attributionMedium: 'cpc',
                    attributionCampaign: 'spring-sale',
                    occurredAt: new DateTimeImmutable('2026-03-28T12:00:00+02:00'),
                ),
            ],
        );

        $readModel = new RecordingClicksListReadModel($expectedView);
        $handler = new ListClicksHandler($readModel);

        $result = $handler($query);

        $this->assertSame($query, $readModel->receivedQuery);
        $this->assertSame($expectedView, $result);
        $this->assertSame(2, $result->currentPage);
        $this->assertSame(14, $result->total);
        $this->assertCount(2, $result->items);
    }
}

final class RecordingClicksListReadModel implements ClicksListReadModel
{
    public ?ListClicksQuery $receivedQuery = null;

    public function __construct(
        private readonly ClicksListView $view,
    ) {
    }

    public function __invoke(ListClicksQuery $query): ClicksListView
    {
        $this->receivedQuery = $query;

        return $this->view;
    }
}
