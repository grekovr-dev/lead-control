<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\ListTouches\ListTouchesQuery;
use Inbound\Domain\Touch\TouchType;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentTouchesListReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\TouchModel;
use Tests\TestCase;

final class EloquentTouchesListReadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_paginated_touches_filtered_by_visit_visitor_and_type(): void
    {
        $this->createTouch(
            id: 'touch-1',
            visitId: 'visit-123',
            visitorId: 'visitor-123',
            type: 'messenger_click',
            occurredAt: '2026-03-28 11:00:00',
        );

        $this->createTouch(
            id: 'touch-2',
            visitId: 'visit-123',
            visitorId: 'visitor-123',
            type: 'messenger_click',
            occurredAt: '2026-03-28 11:10:00',
        );

        $this->createTouch(
            id: 'touch-3',
            visitId: 'visit-123',
            visitorId: 'visitor-123',
            type: 'phone_click',
            occurredAt: '2026-03-28 11:20:00',
        );

        $this->createTouch(
            id: 'touch-4',
            visitId: 'visit-999',
            visitorId: 'visitor-999',
            type: 'messenger_click',
            occurredAt: '2026-03-28 11:30:00',
        );

        $readModel = new EloquentTouchesListReadModel();

        $view = $readModel(new ListTouchesQuery(
            visitId: 'visit-123',
            visitorId: 'visitor-123',
            type: TouchType::MessengerClick,
            page: 1,
            perPage: 1,
        ));

        $this->assertSame(1, $view->currentPage);
        $this->assertSame(1, $view->perPage);
        $this->assertSame(2, $view->total);
        $this->assertSame(2, $view->lastPage);
        $this->assertCount(1, $view->items);
        $this->assertSame('touch-2', $view->items[0]->touchId);
        $this->assertSame('visit-123', $view->items[0]->visitId);
        $this->assertSame('visitor-123', $view->items[0]->visitorId);
        $this->assertSame('messenger_click', $view->items[0]->type);
        $this->assertSame('Клік по месенджеру', $view->items[0]->typeLabel);
    }

    public function test_it_returns_unfiltered_touches_in_reverse_chronological_order(): void
    {
        $this->createTouch(
            id: 'touch-1',
            visitId: 'visit-1',
            visitorId: 'visitor-1',
            type: 'phone_click',
            occurredAt: '2026-03-28 11:00:00',
        );

        $this->createTouch(
            id: 'touch-2',
            visitId: 'visit-2',
            visitorId: 'visitor-2',
            type: 'works_click',
            occurredAt: '2026-03-28 11:05:00',
        );

        $readModel = new EloquentTouchesListReadModel();

        $view = $readModel(new ListTouchesQuery());

        $this->assertSame(2, $view->total);
        $this->assertSame(1, $view->lastPage);
        $this->assertCount(2, $view->items);
        $this->assertSame(['touch-2', 'touch-1'], array_map(
            static fn ($item): string => $item->touchId,
            $view->items,
        ));
        $this->assertSame('works_click', $view->items[0]->type);
        $this->assertSame('Клік по роботах', $view->items[0]->typeLabel);
    }

    private function createTouch(
        string $id,
        string $visitId,
        string $visitorId,
        string $type,
        string $occurredAt,
    ): void {
        TouchModel::query()->create([
            'id' => $id,
            'visit_id' => $visitId,
            'visitor_id' => $visitorId,
            'type' => $type,
            'occurred_at' => $occurredAt,
        ]);
    }
}
