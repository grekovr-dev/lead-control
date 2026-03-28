<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Queries\Backoffice\ListVisits;

use DateTimeImmutable;
use Inbound\Application\Queries\Backoffice\ListVisits\ListVisitsHandler;
use Inbound\Application\Queries\Backoffice\ListVisits\ListVisitsQuery;
use Inbound\Application\Queries\Backoffice\ListVisits\VisitListItemView;
use Inbound\Application\Queries\Backoffice\ListVisits\VisitsListReadModel;
use Inbound\Application\Queries\Backoffice\ListVisits\VisitsListView;
use PHPUnit\Framework\TestCase;

final class ListVisitsHandlerTest extends TestCase
{
    public function test_it_passes_the_query_to_the_read_model_and_returns_a_paginated_view(): void
    {
        $query = new ListVisitsQuery(
            visitorId: 'visitor-123',
            firstAttributionSource: 'google',
            firstAttributionMedium: 'cpc',
            lastAttributionSource: 'google',
            lastAttributionMedium: 'organic',
            page: 2,
            perPage: 10,
        );

        $expectedView = new VisitsListView(
            currentPage: 2,
            perPage: 10,
            total: 14,
            lastPage: 2,
            items: [
                new VisitListItemView(
                    visitId: 'visit-2',
                    visitorId: 'visitor-123',
                    firstAttributionSource: 'google',
                    firstAttributionMedium: 'cpc',
                    lastAttributionSource: 'google',
                    lastAttributionMedium: 'organic',
                    startedAt: new DateTimeImmutable('2026-03-28T11:00:00+02:00'),
                    lastTouchedAt: new DateTimeImmutable('2026-03-28T12:05:00+02:00'),
                ),
                new VisitListItemView(
                    visitId: 'visit-1',
                    visitorId: 'visitor-123',
                    firstAttributionSource: 'google',
                    firstAttributionMedium: 'cpc',
                    lastAttributionSource: 'google',
                    lastAttributionMedium: 'organic',
                    startedAt: new DateTimeImmutable('2026-03-28T10:00:00+02:00'),
                    lastTouchedAt: new DateTimeImmutable('2026-03-28T12:00:00+02:00'),
                ),
            ],
        );

        $readModel = new RecordingVisitsListReadModel($expectedView);
        $handler = new ListVisitsHandler($readModel);

        $result = $handler($query);

        $this->assertSame($query, $readModel->receivedQuery);
        $this->assertSame($expectedView, $result);
        $this->assertSame(2, $result->currentPage);
        $this->assertSame(14, $result->total);
        $this->assertCount(2, $result->items);
    }
}

final class RecordingVisitsListReadModel implements VisitsListReadModel
{
    public ?ListVisitsQuery $receivedQuery = null;

    public function __construct(
        private readonly VisitsListView $view,
    ) {
    }

    public function __invoke(ListVisitsQuery $query): VisitsListView
    {
        $this->receivedQuery = $query;

        return $this->view;
    }
}
