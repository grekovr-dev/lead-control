<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Capture;

use App\Http\Cookies\Inbound\Capture\AttributionCookieStore;
use App\Http\Cookies\Inbound\Capture\VisitorIdCookieStore;
use JsonException;
use Tests\TestCase;

final class LandingControllerTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function test_it_bootstraps_visitor_and_attribution_cookies_on_landing_open(): void
    {
        $visitorIdCookieStore = $this->app->make(VisitorIdCookieStore::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withHeader('referer', 'https://google.com/search?q=stretch+ceiling')
            ->get('/?utm_source=google&utm_medium=cpc&utm_campaign=spring-sale&gclid=gclid-1');

        $response->assertOk();
        $response->assertViewIs('pages.landing');
        $response->assertCookieNotExpired($visitorIdCookieStore->cookieName());
        $response->assertCookieNotExpired($attributionCookieStore->cookieName());
        $response->assertCookieMissing('inbound_referrer');

        $visitorCookie = $response->getCookie($visitorIdCookieStore->cookieName());
        $attributionCookie = $response->getCookie($attributionCookieStore->cookieName());

        $this->assertNotNull($visitorCookie);
        $this->assertNotNull($attributionCookie);
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
            'referrer' => 'https://google.com/search?q=stretch+ceiling',
        ], json_decode((string) $attributionCookie->getValue(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function test_it_bootstraps_visitor_and_attribution_cookies_on_geo_landing_open(): void
    {
        $visitorIdCookieStore = $this->app->make(VisitorIdCookieStore::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withHeader('referer', 'https://google.com/search?q=stretch+ceiling')
            ->get('/boryspil?utm_source=google&utm_medium=cpc&utm_campaign=spring-sale&gclid=gclid-1');

        $response->assertOk();
        $response->assertViewIs('pages.landing');
        $response->assertCookieNotExpired($visitorIdCookieStore->cookieName());
        $response->assertCookieNotExpired($attributionCookieStore->cookieName());
        $response->assertCookieMissing('inbound_referrer');

        $visitorCookie = $response->getCookie($visitorIdCookieStore->cookieName());
        $attributionCookie = $response->getCookie($attributionCookieStore->cookieName());

        $this->assertNotNull($visitorCookie);
        $this->assertNotNull($attributionCookie);
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
            'referrer' => 'https://google.com/search?q=stretch+ceiling',
        ], json_decode((string) $attributionCookie->getValue(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function test_it_reuses_existing_visitor_cookie_and_stores_direct_attribution_snapshot(): void
    {
        $visitorIdCookieStore = $this->app->make(VisitorIdCookieStore::class);
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withCookie($visitorIdCookieStore->cookieName(), '550e8400-e29b-41d4-a716-446655440000')
            ->get('/');

        $response->assertOk();
        $response->assertViewIs('pages.landing');
        $response->assertCookie(
            $visitorIdCookieStore->cookieName(),
            '550e8400-e29b-41d4-a716-446655440000',
        );
        $response->assertCookieNotExpired($attributionCookieStore->cookieName());
        $response->assertCookieMissing('inbound_referrer');

        $attributionCookie = $response->getCookie($attributionCookieStore->cookieName());

        $this->assertNotNull($attributionCookie);
        $this->assertSame([
            'source' => 'direct',
            'medium' => 'none',
            'campaign' => null,
            'content' => null,
            'term' => null,
            'gclid' => null,
            'fbclid' => null,
            'msclkid' => null,
            'referrer' => null,
        ], json_decode((string) $attributionCookie->getValue(), true, 512, JSON_THROW_ON_ERROR));
    }

    /**
     * @throws JsonException
     */
    public function test_it_maps_external_referer_to_pending_attribution_when_utm_is_missing(): void
    {
        $attributionCookieStore = $this->app->make(AttributionCookieStore::class);

        $response = $this
            ->withHeader('referer', 'https://www.instagram.com/example-post')
            ->get('/');

        $response->assertOk();
        $response->assertCookieNotExpired($attributionCookieStore->cookieName());
        $response->assertCookieMissing('inbound_referrer');

        $attributionCookie = $response->getCookie($attributionCookieStore->cookieName());

        $this->assertNotNull($attributionCookie);
        $this->assertSame([
            'source' => 'instagram',
            'medium' => 'social',
            'campaign' => null,
            'content' => null,
            'term' => null,
            'gclid' => null,
            'fbclid' => null,
            'msclkid' => null,
            'referrer' => 'https://www.instagram.com/example-post',
        ], json_decode((string) $attributionCookie->getValue(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function test_it_wires_initial_landing_click_tracking_into_the_view(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('id="landing-capture-config"', $content);
        $this->assertStringContainsString('landingCapture()', $content);
        $this->assertStringContainsString('x-init="init()"', $content);

        foreach ([
            route('capture.click'),
            route('capture.touch'),
            route('capture.leads.form'),
            route('capture.leads.phone-click'),
        ] as $route) {
            $this->assertStringContainsString(str_replace('/', '\\/', $route), $content);
        }
    }

    public function test_it_exposes_service_structured_data_for_search_engines(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('type="application/ld+json"', $content);
        $this->assertStringContainsString('"@context":"https://schema.org"', $content);
        $this->assertStringContainsString('"@type":"Service"', $content);
        $this->assertStringContainsString('"name":"Натяжні стелі в Києві та області"', $content);
        $this->assertStringContainsString('"url":"'.route('landing').'"', $content);
        $this->assertStringContainsString('"name":"Добрі стелі"', $content);
    }

    public function test_it_exposes_favicon_links_for_browsers_and_mobile_devices(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('favicon.svg', $content);
        $this->assertStringContainsString('favicon-32x32.png', $content);
        $this->assertStringContainsString('favicon-16x16.png', $content);
        $this->assertStringContainsString('apple-touch-icon.png', $content);
        $this->assertStringContainsString('favicon.ico', $content);
    }

    public function test_it_embeds_google_tag_manager_in_the_shared_layout(): void
    {
        config()->set('services.google_tag_manager.id', 'GTM-N354DDJ9');

        $response = $this->get('/');

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('googletagmanager.com/gtm.js?id=', $content);
        $this->assertStringContainsString('googletagmanager.com/ns.html?id=', $content);
        $this->assertStringContainsString('GTM-N354DDJ9', $content);
        $this->assertStringContainsString('dataLayer', $content);
    }
}
