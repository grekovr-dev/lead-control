<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Capture;

use App\Http\Cookies\Inbound\Capture\AttributionCookieStore;
use App\Http\Cookies\Inbound\Capture\ReferrerCookieStore;
use App\Http\Resolvers\Inbound\Capture\VisitorIdCookieResolver;
use JsonException;
use Tests\TestCase;

final class LandingControllerTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function test_it_bootstraps_visitor_and_attribution_cookies_on_landing_open(): void
    {
        $visitorIdCookieResolver = $this->app->make(VisitorIdCookieResolver::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);
        $referrerCookieStore = $this->app->make(ReferrerCookieStore::class);

        $response = $this
            ->withHeader('referer', 'https://google.com/search?q=stretch+ceiling')
            ->get('/?utm_source=google&utm_medium=cpc&utm_campaign=spring-sale&gclid=gclid-1');

        $response->assertOk();
        $response->assertViewIs('pages.landing');
        $response->assertCookieNotExpired($visitorIdCookieResolver->cookieName());
        $response->assertCookieNotExpired($attributionCookieStore->cookieName());
        $response->assertCookieNotExpired($referrerCookieStore->cookieName());

        $visitorCookie = $response->getCookie($visitorIdCookieResolver->cookieName());
        $attributionCookie = $response->getCookie($attributionCookieStore->cookieName());
        $referrerCookie = $response->getCookie($referrerCookieStore->cookieName());

        $this->assertNotNull($visitorCookie);
        $this->assertNotNull($attributionCookie);
        $this->assertNotNull($referrerCookie);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            (string) $visitorCookie->getValue(),
        );
        $this->assertSame([
            'source' => 'google',
            'medium' => 'cpc',
            'campaign' => 'spring-sale',
            'content' => null,
            'term' => null,
            'gclid' => 'gclid-1',
            'fbclid' => null,
            'msclkid' => null,
        ], json_decode((string) $attributionCookie->getValue(), true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame('https://google.com/search?q=stretch+ceiling', $referrerCookie->getValue());
    }

    public function test_it_reuses_existing_visitor_cookie_and_skips_empty_attribution_snapshot(): void
    {
        $visitorIdCookieResolver = $this->app->make(VisitorIdCookieResolver::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);
        $referrerCookieStore = $this->app->make(ReferrerCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieResolver->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
            ->get('/');

        $response->assertOk();
        $response->assertViewIs('pages.landing');
        $response->assertCookie(
            $visitorIdCookieResolver->cookieName(),
            '550e8400-e29b-41d4-a716-446655440000',
        );
        $response->assertCookieMissing($attributionCookieStore->cookieName());
        $response->assertCookieMissing($referrerCookieStore->cookieName());
    }
}
