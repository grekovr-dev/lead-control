<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Capture;

use App\Http\Cookies\Inbound\Capture\AttributionCookieStore;
use App\Http\Resolvers\Inbound\Capture\VisitorIdCookieResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'started_at' => now()->subMinutes(10),
            'last_touched_at' => now()->subMinutes(5),
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'remarketing',
        ]);

        $visitorIdCookieResolver = $this->app->make(VisitorIdCookieResolver::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieResolver->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
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
                'phone' => ' +380501112233 ',
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
            'attribution_source' => 'google',
            'attribution_medium' => 'cpc',
            'attribution_campaign' => 'spring-sale',
        ]);
        $this->assertDatabaseCount('visits', 1);
    }

    /**
     * @throws JsonException
     */
    public function test_form_endpoint_returns_conflict_when_active_visit_is_missing(): void
    {
        $visitorIdCookieResolver = $this->app->make(VisitorIdCookieResolver::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieResolver->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
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
        $response->assertJsonPath('code', 'active_visit_not_found');
        $response->assertJsonPath('message', 'Cannot create lead from form without an active visit.');
        $this->assertDatabaseCount('leads', 0);
    }

    public function test_form_endpoint_returns_validation_error_when_phone_is_missing(): void
    {
        $visitorIdCookieResolver = $this->app->make(VisitorIdCookieResolver::class);

        $response = $this
            ->withCookie($visitorIdCookieResolver->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
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

    /**
     * @throws JsonException
     */
    public function test_phone_click_endpoint_creates_lead_using_existing_active_visit(): void
    {
        VisitModel::query()->create([
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'started_at' => now()->subMinutes(10),
            'last_touched_at' => now()->subMinutes(5),
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'remarketing',
        ]);

        $visitorIdCookieResolver = $this->app->make(VisitorIdCookieResolver::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieResolver->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
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

        $leadId = (string) $response->json('data.leadId');

        $this->assertNotSame('', $leadId);
        $this->assertDatabaseCount('leads', 1);
        $this->assertDatabaseHas('leads', [
            'id' => $leadId,
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'visit_id' => 'visit-existing',
            'name' => null,
            'phone' => null,
            'status' => 'new',
            'origin' => 'phone_click',
            'attribution_source' => 'google',
            'attribution_medium' => 'cpc',
            'attribution_campaign' => 'spring-sale',
        ]);
        $this->assertDatabaseCount('visits', 1);
    }

    /**
     * @throws JsonException
     */
    public function test_phone_click_endpoint_returns_conflict_when_active_visit_is_missing(): void
    {
        $visitorIdCookieResolver = $this->app->make(VisitorIdCookieResolver::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieResolver->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
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
        $response->assertJsonPath('code', 'active_visit_not_found');
        $response->assertJsonPath('message', 'Cannot create lead from phone click without an active visit.');
        $this->assertDatabaseCount('leads', 0);
    }
}
