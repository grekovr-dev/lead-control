<?php

declare(strict_types=1);

namespace Tests\Feature\Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\GetLeadStatusReportQuery;
use Inbound\Domain\Lead\LeadStatus;
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

    private function createLead(string $id, string $status): void
    {
        LeadModel::query()->create([
            'id' => $id,
            'visitor_id' => 'visitor-'.$id,
            'visit_id' => 'visit-'.$id,
            'name' => null,
            'phone' => null,
            'status' => $status,
            'origin' => 'form',
            'created_at' => '2026-03-29 12:00:00',
        ]);
    }
}
