<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Capture;

use App\Http\Cookies\Inbound\Capture\AttributionCookieStore;
use App\Http\Cookies\Inbound\Capture\VisitorIdCookieStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use JsonException;
use Tests\TestCase;

final class CreateLeadControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @throws JsonException
     */
    public function test_form_endpoint_creates_lead_using_existing_active_visit(): void
    {
        VisitModel::query()->create([
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'landing_url' => 'https://example.com/landing',
            'started_at' => now()->subMinutes(10),
            'last_touched_at' => now()->subMinutes(5),
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'remarketing',
        ]);

        $visitorIdCookieStore = $this->app->make(VisitorIdCookieStore::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieStore->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
            ->withCookie($attributionCookieStore->cookieName(), json_encode([
                'source' => 'google',
                'medium' => 'cpc',
                'campaign' => 'spring-sale',
                'content' => null,
                'term' => null,
                'gclid' => null,
                'fbclid' => null,
                'msclkid' => null,
            ], JSON_THROW_ON_ERROR))
            ->withCredentials()
            ->postJson(route('capture.leads.form'), [
                'name' => ' John Doe ',
                'phone' => ' +380 (50) 111-22-33 ',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('data.visitId', 'visit-existing');
        $response->assertJsonPath('data.visitorId', '550e8400-e29b-41d4-a716-446655440000');
        $response->assertJsonPath('data.origin', 'form');

        $leadId = (string) $response->json('data.leadId');

        $this->assertNotSame('', $leadId);
        $this->assertDatabaseCount('leads', 1);
        $this->assertDatabaseHas('leads', [
            'id' => $leadId,
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'visit_id' => 'visit-existing',
            'name' => 'John Doe',
            'phone' => '+380501112233',
            'status' => 'new',
            'origin' => 'form',
            'landing_url' => 'https://example.com/landing',
            'visit_attribution_source' => 'google',
            'visit_attribution_medium' => 'cpc',
            'visitor_attribution_source' => 'google',
            'visitor_attribution_medium' => 'cpc',
        ]);
        $this->assertDatabaseCount('visits', 1);
    }

    /**
     * @throws JsonException
     */
    public function test_form_endpoint_returns_conflict_when_active_visit_is_missing(): void
    {
        $visitorIdCookieStore = $this->app->make(VisitorIdCookieStore::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieStore->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
            ->withCookie($attributionCookieStore->cookieName(), json_encode([
                'source' => 'google',
                'medium' => 'cpc',
                'campaign' => null,
                'content' => null,
                'term' => null,
                'gclid' => null,
                'fbclid' => null,
                'msclkid' => null,
            ], JSON_THROW_ON_ERROR))
            ->withCredentials()
            ->postJson(route('capture.leads.form'), [
                'name' => 'John Doe',
                'phone' => '+380501112233',
            ]);

        $response->assertStatus(409);
        $response->assertJsonPath('ok', false);
        $response->assertJsonPath('code', 'current_visit_not_found');
        $response->assertJsonPath('message', 'Cannot create lead from form without a current visit.');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_form_endpoint_returns_conflict_when_visitor_cookie_is_missing(): void
    {
        $response = $this
            ->withCredentials()
            ->postJson(route('capture.leads.form'), [
                'name' => 'John Doe',
                'phone' => '+380501112233',
            ]);

        $response->assertStatus(409);
        $response->assertJsonPath('ok', false);
        $response->assertJsonPath('code', 'visitor_id_not_found');
        $response->assertJsonPath('message', 'Visitor context is missing.');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_form_endpoint_returns_validation_error_when_phone_is_missing(): void
    {
        $visitorIdCookieStore = $this->app->make(VisitorIdCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieStore->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
            ->withCredentials()
            ->postJson(route('capture.leads.form'), [
                'name' => 'John Doe',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('ok', false);
        $response->assertJsonPath('code', 'validation_error');
        $response->assertJsonPath('message', 'The given data was invalid.');
        $response->assertJsonPath('errors.phone.0', 'The phone field is required.');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_form_endpoint_returns_validation_error_when_phone_is_not_e164(): void
    {
        $visitorIdCookieStore = $this->app->make(VisitorIdCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieStore->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
            ->withCredentials()
            ->postJson(route('capture.leads.form'), [
                'name' => 'John Doe',
                'phone' => '380501112233',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('ok', false);
        $response->assertJsonPath('code', 'validation_error');
        $response->assertJsonPath('message', 'The given data was invalid.');
        $response->assertJsonPath('errors.phone.0', 'The phone field format is invalid.');
        $this->assertDatabaseCount('leads', 0);
    }

    /**
     * @throws JsonException
     */
    public function test_phone_click_endpoint_creates_lead_using_existing_active_visit(): void
    {
        VisitModel::query()->create([
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'landing_url' => 'https://example.com/landing',
            'started_at' => now()->subMinutes(10),
            'last_touched_at' => now()->subMinutes(5),
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'remarketing',
        ]);

        $visitorIdCookieStore = $this->app->make(VisitorIdCookieStore::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieStore->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
            ->withCookie($attributionCookieStore->cookieName(), json_encode([
                'source' => 'google',
                'medium' => 'cpc',
                'campaign' => 'spring-sale',
                'content' => null,
                'term' => null,
                'gclid' => null,
                'fbclid' => null,
                'msclkid' => null,
            ], JSON_THROW_ON_ERROR))
            ->withCredentials()
            ->postJson(route('capture.leads.phone-click'));

        $response->assertCreated();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('data.visitId', 'visit-existing');
        $response->assertJsonPath('data.visitorId', '550e8400-e29b-41d4-a716-446655440000');
        $response->assertJsonPath('data.origin', 'phone_click');
        $response->assertJsonPath('data.resultType', 'lead');
        $this->assertNotSame('', (string) $response->json('data.resultId'));
        $this->assertArrayNotHasKey('leadId', (array) $response->json('data'));

        $leadId = (string) $response->json('data.resultId');
        $this->assertDatabaseCount('leads', 1);
        $this->assertDatabaseCount('touches', 0);
        $this->assertDatabaseHas('leads', [
            'id' => $leadId,
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'visit_id' => 'visit-existing',
            'name' => null,
            'phone' => null,
            'status' => 'new',
            'origin' => 'phone_click',
            'landing_url' => 'https://example.com/landing',
            'visit_attribution_source' => 'google',
            'visit_attribution_medium' => 'cpc',
            'visitor_attribution_source' => 'google',
            'visitor_attribution_medium' => 'cpc',
        ]);
        $this->assertDatabaseCount('visits', 1);
    }

    /**
     * @throws JsonException
     */
    public function test_phone_click_endpoint_creates_touch_when_phone_click_lead_already_exists_in_active_visit(): void
    {
        VisitModel::query()->create([
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'landing_url' => 'https://example.com/landing',
            'started_at' => now()->subMinutes(10),
            'last_touched_at' => now()->subMinutes(5),
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'remarketing',
        ]);

        LeadModel::query()->create([
            'id' => 'lead-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'visit_id' => 'visit-existing',
            'name' => null,
            'phone' => null,
            'status' => 'new',
            'origin' => 'phone_click',
            'created_at' => now()->subMinutes(4),
            'landing_url' => 'https://example.com/landing',
            'visit_attribution_source' => 'google',
            'visit_attribution_medium' => 'cpc',
            'visitor_attribution_source' => 'google',
            'visitor_attribution_medium' => 'cpc',
        ]);

        $visitorIdCookieStore = $this->app->make(VisitorIdCookieStore::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieStore->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
            ->withCookie($attributionCookieStore->cookieName(), json_encode([
                'source' => 'google',
                'medium' => 'remarketing',
                'campaign' => 'spring-sale',
                'content' => null,
                'term' => null,
                'gclid' => null,
                'fbclid' => null,
                'msclkid' => null,
            ], JSON_THROW_ON_ERROR))
            ->withCredentials()
            ->postJson(route('capture.leads.phone-click'));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('data.visitId', 'visit-existing');
        $response->assertJsonPath('data.visitorId', '550e8400-e29b-41d4-a716-446655440000');
        $response->assertJsonPath('data.type', 'phone_click');
        $response->assertJsonPath('data.resultType', 'touch');
        $this->assertNotSame('', (string) $response->json('data.resultId'));
        $this->assertArrayNotHasKey('touchId', (array) $response->json('data'));

        $touchId = (string) $response->json('data.resultId');
        $this->assertDatabaseCount('leads', 1);
        $this->assertDatabaseCount('touches', 1);
        $this->assertDatabaseHas('touches', [
            'id' => $touchId,
            'visit_id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'type' => 'phone_click',
        ]);
    }

    /**
     * @throws JsonException
     */
    public function test_phone_click_endpoint_creates_phone_click_lead_when_only_form_lead_exists_in_active_visit(): void
    {
        VisitModel::query()->create([
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'landing_url' => 'https://example.com/landing',
            'started_at' => now()->subMinutes(10),
            'last_touched_at' => now()->subMinutes(5),
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'remarketing',
        ]);

        LeadModel::query()->create([
            'id' => 'lead-form-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'visit_id' => 'visit-existing',
            'name' => 'John Doe',
            'phone' => '+380501112233',
            'status' => 'new',
            'origin' => 'form',
            'created_at' => now()->subMinutes(4),
            'landing_url' => 'https://example.com/landing',
            'visit_attribution_source' => 'google',
            'visit_attribution_medium' => 'cpc',
            'visitor_attribution_source' => 'google',
            'visitor_attribution_medium' => 'cpc',
        ]);

        $visitorIdCookieStore = $this->app->make(VisitorIdCookieStore::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieStore->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
            ->withCookie($attributionCookieStore->cookieName(), json_encode([
                'source' => 'google',
                'medium' => 'remarketing',
                'campaign' => 'spring-sale',
                'content' => null,
                'term' => null,
                'gclid' => null,
                'fbclid' => null,
                'msclkid' => null,
            ], JSON_THROW_ON_ERROR))
            ->withCredentials()
            ->postJson(route('capture.leads.phone-click'));

        $response->assertCreated();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('data.visitId', 'visit-existing');
        $response->assertJsonPath('data.visitorId', '550e8400-e29b-41d4-a716-446655440000');
        $response->assertJsonPath('data.origin', 'phone_click');
        $response->assertJsonPath('data.resultType', 'lead');
        $this->assertNotSame('', (string) $response->json('data.resultId'));
        $this->assertArrayNotHasKey('leadId', (array) $response->json('data'));

        $leadId = (string) $response->json('data.resultId');
        $this->assertDatabaseCount('leads', 2);
        $this->assertDatabaseCount('touches', 0);
        $this->assertDatabaseHas('leads', [
            'id' => $leadId,
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'visit_id' => 'visit-existing',
            'origin' => 'phone_click',
        ]);
    }

    /**
     * @throws JsonException
     */
    public function test_phone_click_endpoint_returns_conflict_when_active_visit_is_missing(): void
    {
        $visitorIdCookieStore = $this->app->make(VisitorIdCookieStore::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieStore->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
            ->withCookie($attributionCookieStore->cookieName(), json_encode([
                'source' => 'google',
                'medium' => 'cpc',
                'campaign' => null,
                'content' => null,
                'term' => null,
                'gclid' => null,
                'fbclid' => null,
                'msclkid' => null,
            ], JSON_THROW_ON_ERROR))
            ->withCredentials()
            ->postJson(route('capture.leads.phone-click'));

        $response->assertStatus(409);
        $response->assertJsonPath('ok', false);
        $response->assertJsonPath('code', 'current_visit_not_found');
        $response->assertJsonPath('message', 'Cannot create lead from phone click without a current visit.');
        $this->assertDatabaseCount('leads', 0);
        $this->assertDatabaseCount('touches', 0);
    }

    public function test_phone_click_endpoint_returns_conflict_when_visitor_cookie_is_missing(): void
    {
        $response = $this
            ->withCredentials()
            ->postJson(route('capture.leads.phone-click'));

        $response->assertStatus(409);
        $response->assertJsonPath('ok', false);
        $response->assertJsonPath('code', 'visitor_id_not_found');
        $response->assertJsonPath('message', 'Visitor context is missing.');
        $this->assertDatabaseCount('leads', 0);
        $this->assertDatabaseCount('touches', 0);
    }
}
