<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\GetLeadStatusReportQuery;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\Shared\DateRange;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\ReadModel\EloquentLeadStatusReportReadModel;
use Tests\TestCase;

final class EloquentLeadStatusReportReadModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_status_distribution_for_all_defined_statuses(): void
    {
        $this->createLead('lead-1', 'new');
        $this->createLead('lead-2', 'new');
        $this->createLead('lead-3', 'contacted');
        $this->createLead('lead-4', 'won');

        $readModel = new EloquentLeadStatusReportReadModel();

        $report = $readModel(new GetLeadStatusReportQuery());

        $this->assertSame(4, $report->leadsCount);
        $this->assertCount(count(LeadStatus::cases()), $report->rows);

        $newRow = $report->rows[0];
        $this->assertSame('new', $newRow->status);
        $this->assertSame('Новий', $newRow->statusLabel);
        $this->assertSame(2, $newRow->leadsCount);
        $this->assertSame(50.0, $newRow->shareOfTotalRate);

        $contactedRow = $report->rows[1];
        $this->assertSame('contacted', $contactedRow->status);
        $this->assertSame(1, $contactedRow->leadsCount);
        $this->assertSame(25.0, $contactedRow->shareOfTotalRate);

        $qualifiedRow = $report->rows[2];
        $this->assertSame('qualified', $qualifiedRow->status);
        $this->assertSame(0, $qualifiedRow->leadsCount);
        $this->assertSame(0.0, $qualifiedRow->shareOfTotalRate);

        $wonRow = $report->rows[5];
        $this->assertSame('won', $wonRow->status);
        $this->assertSame(1, $wonRow->leadsCount);
        $this->assertSame(25.0, $wonRow->shareOfTotalRate);
    }

    public function test_it_returns_zero_rates_when_there_are_no_leads(): void
    {
        $readModel = new EloquentLeadStatusReportReadModel();

        $report = $readModel(new GetLeadStatusReportQuery());

        $this->assertSame(0, $report->leadsCount);
        $this->assertCount(count(LeadStatus::cases()), $report->rows);

        foreach ($report->rows as $row) {
            $this->assertSame(0, $row->leadsCount);
            $this->assertSame(0.0, $row->shareOfTotalRate);
        }
    }

    public function test_it_filters_leads_by_created_at_range_when_date_range_is_provided(): void
    {
        $this->createLead('lead-1', 'new', '2026-03-01 09:00:00');
        $this->createLead('lead-2', 'new', '2026-03-20 12:00:00');
        $this->createLead('lead-3', 'contacted', '2026-04-02 08:00:00');

        $readModel = new EloquentLeadStatusReportReadModel();

        $report = $readModel(new GetLeadStatusReportQuery(
            leadCreatedAtRange: new DateRange(
                fromInclusive: new DateTimeImmutable('2026-03-01 00:00:00'),
                toExclusive: new DateTimeImmutable('2026-04-01 00:00:00'),
            ),
        ));

        $this->assertSame(2, $report->leadsCount);

        $newRow = $report->rows[0];
        $this->assertSame('new', $newRow->status);
        $this->assertSame(2, $newRow->leadsCount);
        $this->assertSame(100.0, $newRow->shareOfTotalRate);

        $contactedRow = $report->rows[1];
        $this->assertSame('contacted', $contactedRow->status);
        $this->assertSame(0, $contactedRow->leadsCount);
        $this->assertSame(0.0, $contactedRow->shareOfTotalRate);
    }

    public function test_it_filters_leads_by_created_at_with_only_lower_boundary(): void
    {
        $this->createLead('lead-1', 'new', '2026-03-19 23:59:59');
        $this->createLead('lead-2', 'contacted', '2026-03-20 00:00:00');
        $this->createLead('lead-3', 'won', '2026-03-30 08:00:00');

        $readModel = new EloquentLeadStatusReportReadModel();

        $report = $readModel(new GetLeadStatusReportQuery(
            leadCreatedAtRange: new DateRange(
                fromInclusive: new DateTimeImmutable('2026-03-20 00:00:00'),
                toExclusive: null,
            ),
        ));

        $this->assertSame(2, $report->leadsCount);
        $this->assertSame(0, $report->rows[0]->leadsCount);
        $this->assertSame(1, $report->rows[1]->leadsCount);
        $this->assertSame(1, $report->rows[5]->leadsCount);
    }

    public function test_it_filters_leads_by_created_at_with_only_upper_boundary(): void
    {
        $this->createLead('lead-1', 'new', '2026-03-10 09:00:00');
        $this->createLead('lead-2', 'contacted', '2026-03-20 23:59:59');
        $this->createLead('lead-3', 'won', '2026-03-21 00:00:00');

        $readModel = new EloquentLeadStatusReportReadModel();

        $report = $readModel(new GetLeadStatusReportQuery(
            leadCreatedAtRange: new DateRange(
                fromInclusive: null,
                toExclusive: new DateTimeImmutable('2026-03-21 00:00:00'),
            ),
        ));

        $this->assertSame(2, $report->leadsCount);
        $this->assertSame(1, $report->rows[0]->leadsCount);
        $this->assertSame(1, $report->rows[1]->leadsCount);
        $this->assertSame(0, $report->rows[5]->leadsCount);
    }

    private function createLead(string $id, string $status, string $createdAt = '2026-03-29 12:00:00'): void
    {
        LeadModel::query()->create([
            'id' => $id,
            'visitor_id' => 'visitor-'.$id,
            'visit_id' => 'visit-'.$id,
            'name' => null,
            'phone' => null,
            'status' => $status,
            'origin' => 'form',
            'created_at' => $createdAt,
        ]);
    }
}
