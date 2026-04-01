<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Capture;

use App\Http\Cookies\Inbound\Capture\AttributionCookieStore;
use App\Http\Cookies\Inbound\Capture\VisitorIdCookieStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inbound\Domain\Click\Click;
use Inbound\Domain\Click\ClickId;
use Inbound\Domain\Click\ClickRepository;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use JsonException;
use RuntimeException;
use Tests\TestCase;

final class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @throws JsonException
     */
    public function test_click_endpoint_creates_click_and_new_visit_from_cookie_context(): void
    {
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
                'gclid' => 'gclid-1',
                'fbclid' => null,
                'msclkid' => null,
                'referrer' => 'https://google.com/search?q=stretch+ceiling',
            ], JSON_THROW_ON_ERROR))
            ->withCredentials()
            ->postJson(route('capture.click'));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('data.visitorId', '550e8400-e29b-41d4-a716-446655440000');
        $this->assertNotNull($response->getCookie($attributionCookieStore->cookieName()));
        $this->assertLessThan(time(), $response->getCookie($attributionCookieStore->cookieName())->getExpiresTime());

        $clickId = (string) $response->json('data.clickId');
        $visitId = (string) $response->json('data.visitId');

        $this->assertNotSame('', $clickId);
        $this->assertNotSame('', $visitId);
        $this->assertDatabaseCount('clicks', 1);
        $this->assertDatabaseCount('visits', 1);
        $this->assertDatabaseHas('clicks', [
            'id' => $clickId,
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'visit_id' => $visitId,
            'landing_url' => route('landing'),
            'attribution_referrer' => 'https://google.com/search?q=stretch+ceiling',
            'attribution_source' => 'google',
            'attribution_medium' => 'cpc',
            'attribution_campaign' => 'spring-sale',
            'attribution_gclid' => 'gclid-1',
        ]);
        $this->assertDatabaseHas('visits', [
            'id' => $visitId,
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'landing_url' => route('landing'),
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'first_attribution_referrer' => 'https://google.com/search?q=stretch+ceiling',
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'cpc',
            'last_attribution_referrer' => 'https://google.com/search?q=stretch+ceiling',
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

        $visitorIdCookieStore = $this->app->make(VisitorIdCookieStore::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieStore->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
            ->withCookie($attributionCookieStore->cookieName(), json_encode([
                'source' => 'google',
                'medium' => 'remarketing',
                'campaign' => null,
                'content' => null,
                'term' => null,
                'gclid' => null,
                'fbclid' => null,
                'msclkid' => null,
                'referrer' => 'https://facebook.com/ad-1',
            ], JSON_THROW_ON_ERROR))
            ->withCredentials()
            ->postJson(route('capture.click'));

        $response->assertOk();
        $response->assertJsonPath('ok', true);
        $response->assertJsonPath('data.visitorId', '550e8400-e29b-41d4-a716-446655440000');
        $response->assertJsonPath('data.visitId', 'visit-existing');
        $this->assertNotNull($response->getCookie($attributionCookieStore->cookieName()));
        $this->assertLessThan(time(), $response->getCookie($attributionCookieStore->cookieName())->getExpiresTime());

        $clickId = (string) $response->json('data.clickId');

        $this->assertNotSame('', $clickId);
        $this->assertDatabaseCount('clicks', 1);
        $this->assertDatabaseCount('visits', 1);
        $this->assertDatabaseHas('clicks', [
            'id' => $clickId,
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'visit_id' => 'visit-existing',
            'landing_url' => route('landing'),
            'attribution_referrer' => 'https://facebook.com/ad-1',
            'attribution_source' => 'google',
            'attribution_medium' => 'remarketing',
        ]);
        $this->assertDatabaseHas('visits', [
            'id' => 'visit-existing',
            'visitor_id' => '550e8400-e29b-41d4-a716-446655440000',
            'landing_url' => null,
            'first_attribution_source' => 'google',
            'first_attribution_medium' => 'cpc',
            'first_attribution_referrer' => null,
            'last_attribution_source' => 'google',
            'last_attribution_medium' => 'remarketing',
            'last_attribution_referrer' => 'https://facebook.com/ad-1',
        ]);
    }

    /**
     * @throws JsonException
     */
    public function test_click_endpoint_does_not_clear_pending_attribution_cookie_when_action_fails(): void
    {
        $visitorIdCookieStore = $this->app->make(VisitorIdCookieStore::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $this->app->instance(ClickRepository::class, new class implements ClickRepository
        {
            public function save(Click $click): void
            {
                throw new RuntimeException('Click registration failed.');
            }

            public function findById(ClickId $id): ?Click
            {
                return null;
            }
        });

        $response = $this
            ->withCookie($visitorIdCookieStore->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
            ->withCookie($attributionCookieStore->cookieName(), json_encode([
                'source' => 'google',
                'medium' => 'cpc',
                'campaign' => 'spring-sale',
                'content' => null,
                'term' => null,
                'gclid' => 'gclid-1',
                'fbclid' => null,
                'msclkid' => null,
                'referrer' => 'https://google.com/search?q=stretch+ceiling',
            ], JSON_THROW_ON_ERROR))
            ->withCredentials()
            ->postJson(route('capture.click'));

        $response->assertStatus(500);
        $this->assertNull($response->getCookie($attributionCookieStore->cookieName()));
        $this->assertDatabaseCount('clicks', 0);
        $this->assertDatabaseCount('visits', 0);
    }

    /**
     * @throws JsonException
     */
    public function test_click_endpoint_returns_conflict_when_visitor_cookie_is_missing(): void
    {
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withCookie($attributionCookieStore->cookieName(), json_encode([
                'source' => 'google',
                'medium' => 'cpc',
                'campaign' => 'spring-sale',
                'content' => null,
                'term' => null,
                'gclid' => 'gclid-1',
                'fbclid' => null,
                'msclkid' => null,
                'referrer' => 'https://google.com/search?q=stretch+ceiling',
            ], JSON_THROW_ON_ERROR))
            ->withCredentials()
            ->postJson(route('capture.click'));

        $response->assertStatus(409);
        $response->assertJsonPath('ok', false);
        $response->assertJsonPath('code', 'visitor_id_not_found');
        $response->assertJsonPath('message', 'Visitor context is missing.');
        $this->assertNull($response->getCookie($attributionCookieStore->cookieName()));
        $this->assertDatabaseCount('clicks', 0);
        $this->assertDatabaseCount('visits', 0);
    }

    /**
     * @throws JsonException
     */
    public function test_touch_endpoint_returns_conflict_when_current_visit_is_missing(): void
    {
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
                'gclid' => 'gclid-1',
                'fbclid' => null,
                'msclkid' => null,
            ], JSON_THROW_ON_ERROR))
            ->withCredentials()
            ->postJson(route('capture.touch'), [
                'type' => 'lead_form_click',
            ]);

        $response->assertStatus(409);
        $response->assertJsonPath('ok', false);
        $response->assertJsonPath('code', 'current_visit_not_found');
        $response->assertJsonPath('message', 'Cannot continue current visit without an existing visit.');

        $this->assertDatabaseCount('touches', 0);
        $this->assertDatabaseCount('visits', 0);
    }

    public function test_touch_endpoint_returns_conflict_when_visitor_cookie_is_missing(): void
    {
        $response = $this
            ->withCredentials()
            ->postJson(route('capture.touch'), [
                'type' => 'messenger_click',
            ]);

        $response->assertStatus(409);
        $response->assertJsonPath('ok', false);
        $response->assertJsonPath('code', 'visitor_id_not_found');
        $response->assertJsonPath('message', 'Visitor context is missing.');
        $this->assertDatabaseCount('touches', 0);
        $this->assertDatabaseCount('visits', 0);
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

        $visitorIdCookieStore = $this->app->make(VisitorIdCookieStore::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieStore->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
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
            'last_attribution_medium' => 'old-medium',
        ]);
    }

    public function test_touch_endpoint_returns_validation_error_for_invalid_type(): void
    {
        $visitorIdCookieStore = $this->app->make(VisitorIdCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieStore->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
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
