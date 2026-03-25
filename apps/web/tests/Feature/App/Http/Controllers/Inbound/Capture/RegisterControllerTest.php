<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Capture;

use App\Http\Cookies\Inbound\Capture\AttributionCookieStore;
use App\Http\Cookies\Inbound\Capture\ReferrerCookieStore;
use App\Http\Resolvers\Inbound\Capture\VisitorIdCookieResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use JsonException;
use Tests\TestCase;

final class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @throws JsonException
     */
    public function test_click_endpoint_creates_click_and_new_visit_from_cookie_context(): void
    {
        $visitorIdCookieResolver = $this->app->make(VisitorIdCookieResolver::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);
        $referrerCookieStore = $this->app->make(ReferrerCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieResolver->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
            ->withCookie($attributionCookieStore->cookieName(), json_encode([
                'source' => 'google',
                'medium' => 'cpc',
                'campaign' => 'spring-sale',
                'content' => null,
                'term' => null,
                'gclid' => 'gclid-1',
                'fbclid' => null,
                'msclkid' => null,
            ], JSON_THROW_ON_ERROR))
            ->withCookie($referrerCookieStore->cookieName(), 'https://google.com/search?q=stretch+ceiling')
            ->withCredentials()
            ->postJson(route('capture.click'));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('data.visitorId', '550e8400-e29b-41d4-a716-446655440000');

        $clickId = (string) $response->json('data.clickId');
        $visitId = (string) $response->json('data.visitId');

        $this->assertNotSame('', $clickId);
        $this->assertNotSame('', $visitId);
        $this->assertDatabaseCount('clicks', 1);
        $this->assertDatabaseCount('visits', 1);
        $this->assertDatabaseHas('clicks', [
            'id' => $clickId,
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'landing_url' => route('landing'),
            'referrer' => 'https://google.com/search?q=stretch+ceiling',
            'attribution_source' => 'google',
            'attribution_medium' => 'cpc',
            'attribution_campaign' => 'spring-sale',
            'attribution_gclid' => 'gclid-1',
        ]);
        $this->assertDatabaseHas('visits', [
            'id' => $visitId,
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'cpc',
        ]);
    }

    /**
     * @throws JsonException
     */
    public function test_click_endpoint_reuses_existing_visit_when_session_continues(): void
    {
        VisitModel::query()->create([
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'started_at' => now()->subMinutes(10),
            'last_touched_at' => now()->subMinutes(5),
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'old-medium',
        ]);

        $visitorIdCookieResolver = $this->app->make(VisitorIdCookieResolver::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);
        $referrerCookieStore = $this->app->make(ReferrerCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieResolver->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
            ->withCookie($attributionCookieStore->cookieName(), json_encode([
                'source' => 'google',
                'medium' => 'remarketing',
                'campaign' => null,
                'content' => null,
                'term' => null,
                'gclid' => null,
                'fbclid' => null,
                'msclkid' => null,
            ], JSON_THROW_ON_ERROR))
            ->withCookie($referrerCookieStore->cookieName(), 'https://facebook.com/ad-1')
            ->withCredentials()
            ->postJson(route('capture.click'));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('data.visitorId', '550e8400-e29b-41d4-a716-446655440000');
        $response->assertJsonPath('data.visitId', 'visit-existing');

        $clickId = (string) $response->json('data.clickId');

        $this->assertNotSame('', $clickId);
        $this->assertDatabaseCount('clicks', 1);
        $this->assertDatabaseCount('visits', 1);
        $this->assertDatabaseHas('clicks', [
            'id' => $clickId,
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'landing_url' => route('landing'),
            'referrer' => 'https://facebook.com/ad-1',
            'attribution_source' => 'google',
            'attribution_medium' => 'remarketing',
        ]);
        $this->assertDatabaseHas('visits', [
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'remarketing',
        ]);
    }

    /**
     * @throws JsonException
     */
    public function test_touch_endpoint_creates_touch_and_new_visit_from_cookie_context(): void
    {
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
                'gclid' => 'gclid-1',
                'fbclid' => null,
                'msclkid' => null,
            ], JSON_THROW_ON_ERROR))
            ->withCredentials()
            ->postJson(route('capture.touch'), [
                'type' => 'lead_form_click',
            ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('data.visitorId', '550e8400-e29b-41d4-a716-446655440000');
        $response->assertJsonPath('data.type', 'lead_form_click');

        $touchId = (string) $response->json('data.touchId');
        $visitId = (string) $response->json('data.visitId');

        $this->assertNotSame('', $touchId);
        $this->assertNotSame('', $visitId);
        $this->assertDatabaseCount('touches', 1);
        $this->assertDatabaseCount('visits', 1);
        $this->assertDatabaseHas('touches', [
            'id' => $touchId,
            'visit_id' => $visitId,
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'type' => 'lead_form_click',
        ]);
        $this->assertDatabaseHas('visits', [
            'id' => $visitId,
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'cpc',
        ]);
    }

    /**
     * @throws JsonException
     */
    public function test_touch_endpoint_reuses_existing_visit_when_session_continues(): void
    {
        VisitModel::query()->create([
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'started_at' => now()->subMinutes(10),
            'last_touched_at' => now()->subMinutes(5),
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'old-medium',
        ]);

        $visitorIdCookieResolver = $this->app->make(VisitorIdCookieResolver::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieResolver->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
            ->withCookie($attributionCookieStore->cookieName(), json_encode([
                'source' => 'google',
                'medium' => 'remarketing',
                'campaign' => null,
                'content' => null,
                'term' => null,
                'gclid' => null,
                'fbclid' => null,
                'msclkid' => null,
            ], JSON_THROW_ON_ERROR))
            ->withCredentials()
            ->postJson(route('capture.touch'), [
                'type' => 'messenger_click',
            ]);

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('data.visitorId', '550e8400-e29b-41d4-a716-446655440000');
        $response->assertJsonPath('data.visitId', 'visit-existing');
        $response->assertJsonPath('data.type', 'messenger_click');

        $touchId = (string) $response->json('data.touchId');

        $this->assertNotSame('', $touchId);
        $this->assertDatabaseCount('touches', 1);
        $this->assertDatabaseCount('visits', 1);
        $this->assertDatabaseHas('touches', [
            'id' => $touchId,
            'visit_id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'type' => 'messenger_click',
        ]);
        $this->assertDatabaseHas('visits', [
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'remarketing',
        ]);
    }

    public function test_touch_endpoint_returns_validation_error_for_invalid_type(): void
    {
        $visitorIdCookieResolver = $this->app->make(VisitorIdCookieResolver::class);

        $response = $this
            ->withCookie($visitorIdCookieResolver->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
            ->withCredentials()
            ->postJson(route('capture.touch'), [
                'type' => 'invalid-type',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('ok', false);
        $response->assertJsonPath('code', 'validation_error');
        $response->assertJsonPath('message', 'The given data was invalid.');
        $response->assertJsonPath('errors.type.0', 'The selected type is invalid.');
        $this->assertDatabaseCount('touches', 0);
        $this->assertDatabaseCount('visits', 0);
    }
}
