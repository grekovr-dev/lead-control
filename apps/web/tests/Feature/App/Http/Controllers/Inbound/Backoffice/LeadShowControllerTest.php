<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Backoffice;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\LeadDetailsView;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\LeadTimelineView;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadStatusTransitionModel;
use Inbound\Infrastructure\Persistence\Eloquent\TouchModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use Tests\TestCase;

final class LeadShowControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_the_lead_details_screen_from_backoffice_queries(): void
    {
        $this->createUser(42);

        VisitModel::query()->create([
            'id' => 'visit-123',
            'visitor_id' => 'visitor-123',
            'started_at' => '2026-03-28 11:30:00',
            'last_touched_at' => '2026-03-28 12:20:00',
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'first_attribution_campaign' => 'spring-sale',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'remarketing',
            'last_attribution_campaign' => 'spring-sale',
        ]);

        VisitModel::query()->create([
            'id' => 'visit-first',
            'visitor_id' => 'visitor-123',
            'started_at' => '2026-03-20 09:00:00',
            'last_touched_at' => '2026-03-20 09:15:00',
            'first_attribution_source' => 'facebook',
            'first_attribution_medium' => 'paid-social',
            'first_attribution_campaign' => 'lookalike',
            'last_attribution_source' => 'facebook',
            'last_attribution_medium' => 'paid-social',
            'last_attribution_campaign' => 'lookalike',
        ]);

        ClickModel::query()->create([
            'id' => 'click-123',
            'visitor_id' => 'visitor-123',
            'landing_url' => 'https://example.com/landing',
            'attribution_referrer' => 'https://google.com/',
            'occurred_at' => '2026-03-28 11:40:00',
        ]);

        TouchModel::query()->create([
            'id' => 'touch-123',
            'visit_id' => 'visit-123',
            'visitor_id' => 'visitor-123',
            'type' => 'messenger_click',
            'occurred_at' => '2026-03-28 11:50:00',
        ]);

        LeadModel::query()->create([
            'id' => 'lead-123',
            'visitor_id' => 'visitor-123',
            'visit_id' => 'visit-123',
            'name' => 'Ірина',
            'phone' => '+380501112233',
            'status' => 'new',
            'origin' => 'form',
            'landing_url' => 'https://example.com/landing',
            'created_at' => '2026-03-28 12:00:00',
            'visit_attribution_source' => 'google',
            'visit_attribution_medium' => 'cpc',
            'visit_attribution_campaign' => 'spring-sale',
            'visitor_attribution_source' => 'facebook',
            'visitor_attribution_medium' => 'paid-social',
            'visitor_attribution_campaign' => 'lookalike',
        ]);

        LeadStatusTransitionModel::query()->create([
            'lead_id' => 'lead-123',
            'from_status' => 'new',
            'to_status' => 'contacted',
            'rule_key' => 'manual_backoffice',
            'changed_at' => '2026-03-28 12:10:00',
        ]);

        DB::table('lead_notes')->insert([
            'lead_id' => 'lead-123',
            'author_id' => 42,
            'note' => 'Need to call back tomorrow.',
            'created_at' => '2026-03-28 12:20:00',
            'updated_at' => '2026-03-28 12:20:00',
        ]);

        $response = $this->get(route('admin.leads.show', ['leadId' => 'lead-123']));

        $response->assertOk();
        $response->assertViewIs('admin.leads.show');
        $response->assertViewHas('details', function ($details): bool {
            return $details instanceof LeadDetailsView
                && $details->lead->leadId === 'lead-123'
                && $details->lead->statusLabel === 'Новий'
                && $details->lead->originLabel === 'Форма'
                && $details->lead->visitAttribution->source === 'google'
                && $details->lead->visitorAttribution->source === 'facebook'
                && $details->visit?->visitId === 'visit-123'
                && $details->preLeadTouchSummary->count === 1
                && $details->preLeadVisitorClickSummary->count === 1;
        });
        $response->assertViewHas('timeline', function ($timeline): bool {
            return $timeline instanceof LeadTimelineView
                && $timeline->leadId === 'lead-123'
                && count($timeline->events) === 5;
        });
        $response->assertSeeText([
            'Лід lead-123',
            'Центральний операційний екран для роботи з лідом, атрибуцією, візитом і хронологією подій.',
            'Назад',
            'lead-123',
            'Контакт ліда',
            'Ірина',
            '+380501112233',
            'Новий',
            'Форма',
            'ID відвідувача',
            'Походження',
            'Атрибуційний контекст ліда',
            'Атрибуція візиту ліда',
            'Атрибуція першого візиту відвідувача',
            'google',
            'facebook',
            'spring-sale',
            'lookalike',
            'Операційний зріз',
            'Пов’язаний візит',
            'visit-123',
            'Перша атрибуція візиту',
            'Остання атрибуція візиту',
            'Дотики до створення ліда',
            'Кліки відвідувача до створення ліда',
            'Таймлайн',
            'Лід створено',
            'Клік по лендингу',
            'Клік по месенджеру',
            'Статус змінено',
            'Додано нотатку',
            'Need to call back tomorrow.',
        ]);
        $response->assertSee('data-lead-details-back-button', false);
        $response->assertSee('href="' . route('admin.leads.index') . '"', false);
    }

    public function test_it_returns_not_found_when_the_lead_is_missing(): void
    {
        $this->get(route('admin.leads.show', ['leadId' => 'missing-lead']))
            ->assertNotFound();
    }

    private function createUser(int $id): void
    {
        DB::table('users')->insert([
            'id' => $id,
            'name' => 'Test User '.$id,
            'email' => 'lead-show-user'.$id.'@example.test',
            'password' => 'password',
            'created_at' => '2026-03-28 11:00:00',
            'updated_at' => '2026-03-28 11:00:00',
        ]);
    }
}
