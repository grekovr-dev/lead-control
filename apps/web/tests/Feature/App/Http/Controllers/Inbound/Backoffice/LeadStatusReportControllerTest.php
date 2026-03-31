<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Backoffice;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\GetLeadStatusReport\LeadStatusReportView;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Tests\TestCase;

final class LeadStatusReportControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_filters_the_report_by_last_7_days_preset(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-30 10:15:00', 'Europe/Kyiv'));

        $this->createLead('lead-1', 'new', '2026-03-24 09:00:00');
        $this->createLead('lead-2', 'contacted', '2026-03-27 12:00:00');
        $this->createLead('lead-3', 'won', '2026-03-30 08:00:00');
        $this->createLead('lead-4', 'new', '2026-03-23 23:59:59');

        $response = $this->get(route('admin.reports.lead-status', [
            'preset' => 'last_7_days',
        ]));

        $response->assertOk();
        $response->assertViewIs('admin.reports.lead-status');
        $response->assertViewHas('report', function ($report): bool {
            if (! $report instanceof LeadStatusReportView) {
                return false;
            }

            return $report->leadsCount === 3
                && $this->rowCount($report, 'new') === 1
                && $this->rowCount($report, 'contacted') === 1
                && $this->rowCount($report, 'won') === 1;
        });
        $response->assertViewHas('filters', fn (array $filters): bool => $filters === [
            'preset' => 'last_7_days',
            'from' => null,
            'to' => null,
        ]);
        $response->assertSee('option value="last_7_days" selected', false);
        $response->assertSeeText([
            'Період створення лідів',
            'Усього лідів',
        ]);
    }

    public function test_it_filters_the_report_by_custom_range_with_both_boundaries(): void
    {
        $this->createLead('lead-1', 'new', '2026-03-09 23:59:59');
        $this->createLead('lead-2', 'new', '2026-03-10 09:00:00');
        $this->createLead('lead-3', 'won', '2026-03-20 18:00:00');
        $this->createLead('lead-4', 'contacted', '2026-03-21 00:00:00');

        $response = $this->get(route('admin.reports.lead-status', [
            'preset' => 'custom',
            'from' => '2026-03-10',
            'to' => '2026-03-20',
        ]));

        $response->assertOk();
        $response->assertViewHas('report', function ($report): bool {
            if (! $report instanceof LeadStatusReportView) {
                return false;
            }

            return $report->leadsCount === 2
                && $this->rowCount($report, 'new') === 1
                && $this->rowCount($report, 'won') === 1
                && $this->rowCount($report, 'contacted') === 0;
        });
        $response->assertViewHas('filters', fn (array $filters): bool => $filters === [
            'preset' => 'custom',
            'from' => '2026-03-10',
            'to' => '2026-03-20',
        ]);
        $response->assertSee('option value="custom" selected', false);
        $response->assertSee('value="2026-03-10"', false);
        $response->assertSee('value="2026-03-20"', false);
    }

    public function test_it_filters_the_report_by_custom_range_with_only_from_boundary(): void
    {
        $this->createLead('lead-1', 'new', '2026-03-19 23:59:59');
        $this->createLead('lead-2', 'contacted', '2026-03-20 00:00:00');
        $this->createLead('lead-3', 'won', '2026-03-30 08:00:00');

        $response = $this->get(route('admin.reports.lead-status', [
            'preset' => 'custom',
            'from' => '2026-03-20',
        ]));

        $response->assertOk();
        $response->assertViewHas('report', function ($report): bool {
            if (! $report instanceof LeadStatusReportView) {
                return false;
            }

            return $report->leadsCount === 2
                && $this->rowCount($report, 'new') === 0
                && $this->rowCount($report, 'contacted') === 1
                && $this->rowCount($report, 'won') === 1;
        });
        $response->assertViewHas('filters', fn (array $filters): bool => $filters === [
            'preset' => 'custom',
            'from' => '2026-03-20',
            'to' => null,
        ]);
        $response->assertSee('value="2026-03-20"', false);
    }

    public function test_it_filters_the_report_by_custom_range_with_only_to_boundary(): void
    {
        $this->createLead('lead-1', 'new', '2026-03-10 09:00:00');
        $this->createLead('lead-2', 'contacted', '2026-03-20 23:59:59');
        $this->createLead('lead-3', 'won', '2026-03-21 00:00:00');

        $response = $this->get(route('admin.reports.lead-status', [
            'preset' => 'custom',
            'to' => '2026-03-20',
        ]));

        $response->assertOk();
        $response->assertViewHas('report', function ($report): bool {
            if (! $report instanceof LeadStatusReportView) {
                return false;
            }

            return $report->leadsCount === 2
                && $this->rowCount($report, 'new') === 1
                && $this->rowCount($report, 'contacted') === 1
                && $this->rowCount($report, 'won') === 0;
        });
        $response->assertViewHas('filters', fn (array $filters): bool => $filters === [
            'preset' => 'custom',
            'from' => null,
            'to' => '2026-03-20',
        ]);
        $response->assertSee('value="2026-03-20"', false);
    }

    private function rowCount(LeadStatusReportView $report, string $status): int
    {
        foreach ($report->rows as $row) {
            if ($row->status === $status) {
                return $row->leadsCount;
            }
        }

        return -1;
    }

    private function createLead(string $id, string $status, string $createdAt): void
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
