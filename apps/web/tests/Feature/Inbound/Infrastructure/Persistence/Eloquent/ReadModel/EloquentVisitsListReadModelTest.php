<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\ListVisits\ListVisitsQuery;
use Inbound\Domain\Shared\DateRange;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentVisitsListReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use Tests\TestCase;

final class EloquentVisitsListReadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_paginated_visits_filtered_by_visitor_and_attribution(): void
    {
        $this->createVisit(
            id: 'visit-1',
            visitorId: 'visitor-123',
            startedAt: '2026-03-28 10:00:00',
            lastTouchedAt: '2026-03-28 11:00:00',
            firstAttributionSource: 'google',
            firstAttributionMedium: 'cpc',
            firstAttributionCampaign: 'spring-sale',
            lastAttributionSource: 'google',
            lastAttributionMedium: 'organic',
        );

        $this->createVisit(
            id: 'visit-2',
            visitorId: 'visitor-123',
            startedAt: '2026-03-28 10:30:00',
            lastTouchedAt: '2026-03-28 11:10:00',
            firstAttributionSource: 'google',
            firstAttributionMedium: 'cpc',
            firstAttributionCampaign: 'spring-sale',
            lastAttributionSource: 'google',
            lastAttributionMedium: 'organic',
        );

        $this->createVisit(
            id: 'visit-3',
            visitorId: 'visitor-123',
            startedAt: '2026-03-28 10:45:00',
            lastTouchedAt: '2026-03-28 11:20:00',
            firstAttributionSource: 'google',
            firstAttributionMedium: 'organic',
            firstAttributionCampaign: 'warmup',
            lastAttributionSource: 'google',
            lastAttributionMedium: 'organic',
        );

        $this->createVisit(
            id: 'visit-4',
            visitorId: 'visitor-999',
            startedAt: '2026-03-28 11:00:00',
            lastTouchedAt: '2026-03-28 11:30:00',
            firstAttributionSource: 'google',
            firstAttributionMedium: 'cpc',
            firstAttributionCampaign: 'spring-sale',
            lastAttributionSource: 'facebook',
            lastAttributionMedium: 'paid-social',
        );

        $readModel = new EloquentVisitsListReadModel;

        $view = $readModel(new ListVisitsQuery(
            visitorId: 'visitor-123',
            firstAttributionSource: 'google',
            firstAttributionMedium: 'cpc',
            firstAttributionCampaign: 'spring-sale',
            lastAttributionSource: 'google',
            lastAttributionMedium: 'organic',
            startedAtRange: new DateRange(
                fromInclusive: new \DateTimeImmutable('2026-03-28 10:00:00'),
                toExclusive: new \DateTimeImmutable('2026-03-28 10:45:00'),
            ),
            page: 1,
            perPage: 1,
        ));

        $this->assertSame(1, $view->currentPage);
        $this->assertSame(1, $view->perPage);
        $this->assertSame(2, $view->total);
        $this->assertSame(2, $view->lastPage);
        $this->assertCount(1, $view->items);
        $this->assertSame('visit-2', $view->items[0]->visitId);
        $this->assertSame('visitor-123', $view->items[0]->visitorId);
        $this->assertSame('google', $view->items[0]->firstAttributionSource);
        $this->assertSame('spring-sale', $view->items[0]->firstAttributionCampaign);
        $this->assertSame('organic', $view->items[0]->lastAttributionMedium);
    }

    public function test_it_returns_unfiltered_visits_in_reverse_last_touched_order(): void
    {
        $this->createVisit(
            id: 'visit-1',
            visitorId: 'visitor-1',
            startedAt: '2026-03-28 10:00:00',
            lastTouchedAt: '2026-03-28 11:00:00',
            firstAttributionSource: 'google',
            firstAttributionMedium: 'cpc',
            firstAttributionCampaign: 'spring-sale',
            lastAttributionSource: 'google',
            lastAttributionMedium: 'cpc',
        );

        $this->createVisit(
            id: 'visit-2',
            visitorId: 'visitor-2',
            startedAt: '2026-03-28 10:05:00',
            lastTouchedAt: '2026-03-28 11:05:00',
            firstAttributionSource: null,
            firstAttributionMedium: null,
            firstAttributionCampaign: null,
            lastAttributionSource: 'facebook',
            lastAttributionMedium: 'paid-social',
        );

        $readModel = new EloquentVisitsListReadModel;

        $view = $readModel(new ListVisitsQuery);

        $this->assertSame(2, $view->total);
        $this->assertSame(1, $view->lastPage);
        $this->assertCount(2, $view->items);
        $this->assertSame(['visit-2', 'visit-1'], array_map(
            static fn ($item): string => $item->visitId,
            $view->items,
        ));
        $this->assertNull($view->items[0]->firstAttributionSource);
        $this->assertSame('facebook', $view->items[0]->lastAttributionSource);
        $this->assertSame('google', $view->items[1]->lastAttributionSource);
    }

    public function test_it_filters_visits_by_missing_first_attribution_dimensions(): void
    {
        $this->createVisit(
            id: 'visit-1',
            visitorId: 'visitor-1',
            startedAt: '2026-03-28 10:00:00',
            lastTouchedAt: '2026-03-28 11:00:00',
            firstAttributionSource: 'google',
            firstAttributionMedium: 'cpc',
            firstAttributionCampaign: null,
            lastAttributionSource: 'google',
            lastAttributionMedium: 'cpc',
        );

        $this->createVisit(
            id: 'visit-2',
            visitorId: 'visitor-2',
            startedAt: '2026-03-28 10:05:00',
            lastTouchedAt: '2026-03-28 11:05:00',
            firstAttributionSource: 'google',
            firstAttributionMedium: 'cpc',
            firstAttributionCampaign: 'spring-sale',
            lastAttributionSource: 'google',
            lastAttributionMedium: 'cpc',
        );

        $readModel = new EloquentVisitsListReadModel;

        $view = $readModel(new ListVisitsQuery(
            firstAttributionSource: 'google',
            firstAttributionMedium: 'cpc',
            firstAttributionCampaignMissing: true,
        ));

        $this->assertSame(1, $view->total);
        $this->assertCount(1, $view->items);
        $this->assertSame('visit-1', $view->items[0]->visitId);
        $this->assertNull($view->items[0]->firstAttributionCampaign);
    }

    private function createVisit(
        string $id,
        string $visitorId,
        string $startedAt,
        string $lastTouchedAt,
        ?string $firstAttributionSource,
        ?string $firstAttributionMedium,
        ?string $firstAttributionCampaign,
        ?string $lastAttributionSource,
        ?string $lastAttributionMedium,
    ): void {
        VisitModel::query()->create([
            'id' => $id,
            'visitor_id' => $visitorId,
            'started_at' => $startedAt,
            'last_touched_at' => $lastTouchedAt,
            'first_attribution_source' => $firstAttributionSource,
            'first_attribution_medium' => $firstAttributionMedium,
            'first_attribution_campaign' => $firstAttributionCampaign,
            'last_attribution_source' => $lastAttributionSource,
            'last_attribution_medium' => $lastAttributionMedium,
        ]);
    }
}
