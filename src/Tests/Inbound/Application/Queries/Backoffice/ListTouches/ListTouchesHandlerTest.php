<?php

declare(strict_types=1);

namespace Tests\Inbound\Application\Queries\Backoffice\ListTouches;

use DateTimeImmutable;
use Inbound\Application\Queries\Backoffice\ListTouches\ListTouchesHandler;
use Inbound\Application\Queries\Backoffice\ListTouches\ListTouchesQuery;
use Inbound\Application\Queries\Backoffice\ListTouches\TouchListItemView;
use Inbound\Application\Queries\Backoffice\ListTouches\TouchesListReadModel;
use Inbound\Application\Queries\Backoffice\ListTouches\TouchesListView;
use Inbound\Domain\Touch\TouchType;
use PHPUnit\Framework\TestCase;

final class ListTouchesHandlerTest extends TestCase
{
    public function test_it_passes_the_query_to_the_read_model_and_returns_a_paginated_view(): void
    {
        $query = new ListTouchesQuery(
            visitId: 'visit-123',
            visitorId: 'visitor-123',
            type: TouchType::MessengerClick,
            page: 2,
            perPage: 10,
        );

        $expectedView = new TouchesListView(
            currentPage: 2,
            perPage: 10,
            total: 14,
            lastPage: 2,
            items: [
                new TouchListItemView(
                    touchId: 'touch-2',
                    visitId: 'visit-123',
                    visitorId: 'visitor-123',
                    type: 'messenger_click',
                    typeLabel: 'Клік по месенджеру',
                    occurredAt: new DateTimeImmutable('2026-03-28T12:05:00+02:00'),
                ),
                new TouchListItemView(
                    touchId: 'touch-1',
                    visitId: 'visit-123',
                    visitorId: 'visitor-123',
                    type: 'messenger_click',
                    typeLabel: 'Клік по месенджеру',
                    occurredAt: new DateTimeImmutable('2026-03-28T12:00:00+02:00'),
                ),
            ],
        );

        $readModel = new RecordingTouchesListReadModel($expectedView);
        $handler = new ListTouchesHandler($readModel);

        $result = $handler($query);

        $this->assertSame($query, $readModel->receivedQuery);
        $this->assertSame($expectedView, $result);
        $this->assertSame(2, $result->currentPage);
        $this->assertSame(14, $result->total);
        $this->assertCount(2, $result->items);
    }
}

final class RecordingTouchesListReadModel implements TouchesListReadModel
{
    public ?ListTouchesQuery $receivedQuery = null;

    public function __construct(
        private readonly TouchesListView $view,
    ) {
    }

    public function __invoke(ListTouchesQuery $query): TouchesListView
    {
        $this->receivedQuery = $query;

        return $this->view;
    }
}
