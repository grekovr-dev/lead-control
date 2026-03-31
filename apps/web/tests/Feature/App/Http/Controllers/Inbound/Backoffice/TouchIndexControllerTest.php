<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Backoffice;

use DateTimeImmutable;
use Inbound\Application\Queries\Backoffice\ListTouches\ListTouchesQuery;
use Inbound\Application\Queries\Backoffice\ListTouches\TouchListItemView;
use Inbound\Application\Queries\Backoffice\ListTouches\TouchesListReadModel;
use Inbound\Application\Queries\Backoffice\ListTouches\TouchesListView;
use Mockery;
use Tests\TestCase;

final class TouchIndexControllerTest extends TestCase
{
    public function test_it_renders_the_touches_list_with_filters_and_paginated_rows(): void
    {
        $readModel = Mockery::mock(TouchesListReadModel::class);
        $readModel
            ->shouldReceive('__invoke')
            ->once()
            ->with(Mockery::on(function (ListTouchesQuery $query): bool {
                return $query->visitId === 'visit-123'
                    && $query->visitorId === 'visitor-123'
                    && $query->type?->value === 'messenger_click'
                    && $query->page === 2
                    && $query->perPage === 50;
            }))
            ->andReturn(new TouchesListView(
                currentPage: 2,
                perPage: 50,
                total: 64,
                lastPage: 2,
                items: [
                    new TouchListItemView(
                        touchId: 'touch-2',
                        visitId: 'visit-123',
                        visitorId: 'visitor-123',
                        type: 'messenger_click',
                        typeLabel: 'Клік по месенджеру',
                        occurredAt: new DateTimeImmutable('2026-03-29 12:05:00'),
                    ),
                ],
            ));

        $this->app->instance(TouchesListReadModel::class, $readModel);

        $this->get(route('admin.touches.index', [
            'visitId' => 'visit-123',
            'visitorId' => 'visitor-123',
            'type' => 'messenger_click',
            'page' => '2',
            'perPage' => '50',
        ]))
            ->assertOk()
            ->assertSeeText([
                'Дотики',
                'Список дотиків',
                'visit-123',
                'visitor-123',
                'Клік по месенджеру',
                'Показано 51–51 із 64.',
            ])
            ->assertSee('value="visit-123"', false)
            ->assertSee('value="visitor-123"', false)
            ->assertSee('option value="messenger_click" selected', false)
            ->assertSee('option value="50" selected', false)
            ->assertSee(route('admin.touches.index', [
                'visitId' => 'visit-123',
                'visitorId' => 'visitor-123',
                'type' => 'messenger_click',
                'perPage' => 50,
                'page' => 1,
            ]));
    }
}
