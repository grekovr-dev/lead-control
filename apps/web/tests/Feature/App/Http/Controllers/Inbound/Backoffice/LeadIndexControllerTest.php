<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Backoffice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Application\Queries\Backoffice\ListLeads\LeadsListView;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Tests\TestCase;

final class LeadIndexControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_operational_leads_list_using_the_backoffice_query(): void
    {
        $this->createLead(1);
        $this->createLead(2);
        $this->createLead(21, 'e8f6b531-3d52-4d8b-b9d8-2f5e4937e51c', 'Ірина');

        for ($i = 3; $i <= 20; $i++) {
            $this->createLead($i);
        }

        $response = $this->get(route('admin.leads.index'));

        $response->assertOk();
        $response->assertViewIs('admin.leads.index');
        $response->assertViewHas('leads', function ($leads): bool {
            return $leads instanceof LeadsListView
                && $leads->currentPage === 1
                && $leads->perPage === 20
                && $leads->total === 21
                && $leads->lastPage === 2
                && count($leads->items) === 20;
        });
        $response->assertSeeText([
            'Ліди',
            'Поточний список',
            'Усього лідів',
            'Показано 1–20 із 21.',
            'Фільтри',
            'Швидкі фільтри застосовуються одразу',
            'Ірина',
            'Lead 02',
            'Новий',
            'Форма',
            'google / cpc',
            'Далі',
        ]);
        $response->assertSee('name="status"', false);
        $response->assertSee('name="origin"', false);
        $response->assertSee('name="attributionSource"', false);
        $response->assertSee('name="attributionMedium"', false);
        $response->assertSee('name="perPage"', false);
        $response->assertSee('@change="$el.form.requestSubmit()"', false);
        $response->assertSeeText('Шукати');
        $response->assertSeeText('e8f6b531-3d52');
        $response->assertSee('href="' . route('admin.leads.show', ['leadId' => 'e8f6b531-3d52-4d8b-b9d8-2f5e4937e51c']) . '"', false);
        $response->assertSee('data-lead-details-source-link', false);
        $response->assertSee('data-copy-value="e8f6b531-3d52-4d8b-b9d8-2f5e4937e51c"', false);
        $response->assertSee('aria-label="Скопіювати повний ID ліда"', false);
        $response->assertDontSeeText('Lead 01');
    }

    public function test_it_renders_the_requested_page_using_default_pagination_parameters(): void
    {
        for ($i = 1; $i <= 21; $i++) {
            $this->createLead($i);
        }

        $response = $this->get(route('admin.leads.index', ['page' => 2]));

        $response->assertOk();
        $response->assertViewHas('leads', function ($leads): bool {
            return $leads instanceof LeadsListView
                && $leads->currentPage === 2
                && $leads->perPage === 20
                && $leads->total === 21
                && $leads->lastPage === 2
                && count($leads->items) === 1;
        });
        $response->assertSeeText([
            'Показано 21–21 із 21.',
            'Lead 01',
            'Назад',
        ]);
        $response->assertDontSeeText('Lead 21');
    }

    public function test_it_filters_leads_by_status_origin_attribution_and_per_page_and_preserves_filters_in_pagination_links(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $this->createLead(
                $i,
                null,
                sprintf('Filtered %02d', $i),
                status: 'new',
                origin: 'form',
                attributionSource: 'google',
                attributionMedium: 'cpc',
            );
        }

        $this->createLead(21, null, 'Wrong status', status: 'contacted', origin: 'form', attributionSource: 'google', attributionMedium: 'cpc');
        $this->createLead(22, null, 'Wrong origin', status: 'new', origin: 'phone_click', attributionSource: 'google', attributionMedium: 'cpc');
        $this->createLead(23, null, 'Wrong source', status: 'new', origin: 'form', attributionSource: 'facebook', attributionMedium: 'cpc');
        $this->createLead(24, null, 'Wrong medium', status: 'new', origin: 'form', attributionSource: 'google', attributionMedium: 'organic');

        $response = $this->get(route('admin.leads.index', [
            'status' => 'new',
            'origin' => 'form',
            'attributionSource' => 'google',
            'attributionMedium' => 'cpc',
            'perPage' => 10,
        ]));

        $response->assertOk();
        $response->assertViewHas('leads', function ($leads): bool {
            return $leads instanceof LeadsListView
                && $leads->currentPage === 1
                && $leads->perPage === 10
                && $leads->total === 11
                && $leads->lastPage === 2
                && count($leads->items) === 10;
        });
        $response->assertSeeText([
            'Показано 1–10 із 11.',
            'Filtered 11',
            'Filtered 02',
            'Далі',
        ]);
        $response->assertDontSeeText([
            'Wrong status',
            'Wrong origin',
            'Wrong source',
            'Wrong medium',
            'Filtered 01',
        ]);
        $response->assertSee('option value="new" selected', false);
        $response->assertSee('option value="form" selected', false);
        $response->assertSee('name="attributionSource"', false);
        $response->assertSee('value="google"', false);
        $response->assertSee('name="attributionMedium"', false);
        $response->assertSee('value="cpc"', false);
        $response->assertSee('option value="10" selected', false);
        $response->assertSee(
            'href="' . e(route('admin.leads.index', [
                'status' => 'new',
                'origin' => 'form',
                'attributionSource' => 'google',
                'attributionMedium' => 'cpc',
                'perPage' => 10,
                'page' => 2,
            ])) . '"',
            false,
        );
    }

    public function test_it_normalizes_invalid_filter_values_to_safe_defaults(): void
    {
        $this->createLead(1, null, 'Default lead');

        $response = $this->get(route('admin.leads.index', [
            'status' => 'unexpected',
            'origin' => 'other',
            'attributionSource' => '   ',
            'attributionMedium' => '',
            'page' => 0,
            'perPage' => 999,
        ]));

        $response->assertOk();
        $response->assertViewHas('leads', function ($leads): bool {
            return $leads instanceof LeadsListView
                && $leads->currentPage === 1
                && $leads->perPage === 20
                && $leads->total === 1
                && $leads->lastPage === 1
                && count($leads->items) === 1;
        });
        $response->assertSeeText([
            'Показано 1–1 із 1.',
            'Default lead',
        ]);
        $response->assertDontSee('option value="unexpected" selected', false);
        $response->assertDontSee('option value="other" selected', false);
        $response->assertDontSee('value="   "', false);
        $response->assertSee('option value="20" selected', false);
    }

    private function createLead(
        int $number,
        ?string $leadId = null,
        ?string $name = null,
        string $status = 'new',
        string $origin = 'form',
        ?string $attributionSource = 'google',
        ?string $attributionMedium = 'cpc',
    ): void
    {
        $minute = $number % 60;

        LeadModel::query()->create([
            'id' => $leadId ?? sprintf('lead-%02d', $number),
            'visitor_id' => sprintf('visitor-%02d', $number),
            'visit_id' => sprintf('visit-%02d', $number),
            'name' => $name ?? sprintf('Lead %02d', $number),
            'phone' => '+380501112233',
            'status' => $status,
            'origin' => $origin,
            'visit_attribution_source' => $attributionSource,
            'visit_attribution_medium' => $attributionMedium,
            'created_at' => sprintf('2026-03-28 10:%02d:00', $minute),
        ]);
    }
}
