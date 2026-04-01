<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\GetFunnelTrends\GetFunnelTrendsQuery;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentFunnelTrendsReadModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use Tests\TestCase;

final class EloquentFunnelTrendsReadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_daily_funnel_trends_in_chronological_order(): void
    {
        $this->createClick('click-1', 'visitor-1', '2026-03-29 10:00:00');
        $this->createClick('click-2', 'visitor-2', '2026-03-29 11:00:00');
        $this->createClick('click-3', 'visitor-3', '2026-03-30 09:00:00');

        $this->createVisit('visit-1', 'visitor-1', '2026-03-29 10:05:00');
        $this->createVisit('visit-2', 'visitor-2', '2026-03-30 09:05:00');
        $this->createVisit('visit-3', 'visitor-3', '2026-03-30 09:10:00');

        $this->createLead('lead-1', 'visitor-1', 'visit-1', '2026-03-29 10:30:00');
        $this->createLead('lead-2', 'visitor-2', 'visit-2', '2026-03-30 10:00:00');

        $readModel = new EloquentFunnelTrendsReadModel();

        $report = $readModel(new GetFunnelTrendsQuery());

        $this->assertSame(3, $report->clicksCount);
        $this->assertSame(3, $report->visitsCount);
        $this->assertSame(2, $report->leadsCount);
        $this->assertSame(66.67, $report->clicksToLeadsConversionRate);
        $this->assertSame(66.67, $report->visitsToLeadsConversionRate);
        $this->assertCount(2, $report->rows);

        $firstDay = $report->rows[0];
        $this->assertSame('2026-03-29', $firstDay->date->format('Y-m-d'));
        $this->assertSame(2, $firstDay->clicksCount);
        $this->assertSame(1, $firstDay->visitsCount);
        $this->assertSame(1, $firstDay->leadsCount);
        $this->assertSame(50.0, $firstDay->clicksToLeadsConversionRate);
        $this->assertSame(100.0, $firstDay->visitsToLeadsConversionRate);

        $secondDay = $report->rows[1];
        $this->assertSame('2026-03-30', $secondDay->date->format('Y-m-d'));
        $this->assertSame(1, $secondDay->clicksCount);
        $this->assertSame(2, $secondDay->visitsCount);
        $this->assertSame(1, $secondDay->leadsCount);
        $this->assertSame(100.0, $secondDay->clicksToLeadsConversionRate);
        $this->assertSame(50.0, $secondDay->visitsToLeadsConversionRate);
    }

    public function test_it_filters_trends_by_date_range(): void
    {
        $this->createClick('click-1', 'visitor-1', '2026-03-28 10:00:00');
        $this->createClick('click-2', 'visitor-2', '2026-03-29 10:00:00');

        $this->createVisit('visit-1', 'visitor-1', '2026-03-28 10:05:00');
        $this->createVisit('visit-2', 'visitor-2', '2026-03-29 10:05:00');

        $this->createLead('lead-1', 'visitor-1', 'visit-1', '2026-03-28 10:30:00');
        $this->createLead('lead-2', 'visitor-2', 'visit-2', '2026-03-29 10:30:00');

        $readModel = new EloquentFunnelTrendsReadModel();

        $report = $readModel(new GetFunnelTrendsQuery(
            dateFrom: new DateTimeImmutable('2026-03-29 00:00:00'),
            dateTo: new DateTimeImmutable('2026-03-29 00:00:00'),
        ));

        $this->assertSame(1, $report->clicksCount);
        $this->assertSame(1, $report->visitsCount);
        $this->assertSame(1, $report->leadsCount);
        $this->assertCount(1, $report->rows);
        $this->assertSame('2026-03-29', $report->rows[0]->date->format('Y-m-d'));
    }

    private function createClick(string $id, string $visitorId, string $occurredAt): void
    {
        ClickModel::query()->create([
            'id' => $id,
            'visitor_id' => $visitorId,
            'landing_url' => 'https://example.com/'.$id,
            'attribution_referrer' => null,
            'occurred_at' => $occurredAt,
        ]);
    }

    private function createVisit(string $id, string $visitorId, string $startedAt): void
    {
        VisitModel::query()->create([
            'id' => $id,
            'visitor_id' => $visitorId,
            'started_at' => $startedAt,
            'last_touched_at' => $startedAt,
        ]);
    }

    private function createLead(string $id, string $visitorId, ?string $visitId, string $createdAt): void
    {
        LeadModel::query()->create([
            'id' => $id,
            'visitor_id' => $visitorId,
            'visit_id' => $visitId,
            'name' => null,
            'phone' => null,
            'status' => 'new',
            'origin' => 'form',
            'created_at' => $createdAt,
        ]);
    }
}
