<?php

declare(strict_types=1);

namespace Tests\Feature\App\Http\Controllers\Inbound\Capture;

use App\Http\Cookies\Inbound\Capture\AttributionCookieStore;
use App\Http\Cookies\Inbound\Capture\VisitorIdCookieStore;
use Illuminate\Support\Facades\Lang;
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
        $response->assertViewHas('landingGeo');
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
        $response->assertViewHas('landingGeo');
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

    public function test_it_exposes_geo_specific_metadata_on_the_boryspil_landing_page(): void
    {
        $response = $this->get('/boryspil');

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('<title>Натяжні стелі в Борисполі під ключ | Добрі стелі</title>', $content);
        $this->assertStringContainsString('<meta name="description" content="Безкоштовний замір, прозорий прорахунок і монтаж натяжних стель у Борисполі та районі. Працюємо швидко та якісно.">', $content);
        $this->assertStringContainsString('<link rel="canonical" href="'.route('landing.geo', ['landingGeoSlug' => 'boryspil']).'">', $content);
        $this->assertStringContainsString('<meta property="og:title" content="Натяжні стелі в Борисполі під ключ | Добрі стелі">', $content);
        $this->assertStringContainsString('<meta property="og:url" content="'.route('landing.geo', ['landingGeoSlug' => 'boryspil']).'">', $content);
        $this->assertStringContainsString('"name":"Натяжні стелі в Борисполі"', $content);
        $this->assertStringContainsString('"name":"Бориспіль"', $content);
        $this->assertStringContainsString('<h1 class="text-4xl font-semibold leading-tight text-slate-900">', $content);
        $this->assertStringContainsString('Натяжні стелі в Борисполі', $content);
        $response->assertSeeText('Швидкий виїзд на замір у Борисполі');
        $this->assertStringContainsString('alt="Натяжна стеля з підсвіткою в сучасному інтер’єрі, Бориспіль"', $content);
    }

    public function test_it_serves_a_russian_boryspil_landing_only_under_the_ru_prefix(): void
    {
        $response = $this->get('/ru/boryspil');

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('<html lang="ru">', $content);
        $this->assertStringContainsString('<title>Натяжные потолки в Борисполе под ключ | Добрі стелі</title>', $content);
        $this->assertStringContainsString('<link rel="canonical" href="'.route('landing.ru.geo', ['landingGeoSlug' => 'boryspil']).'">', $content);
        $this->assertStringContainsString('<link rel="alternate" hreflang="uk" href="'.route('landing.geo', ['landingGeoSlug' => 'boryspil']).'">', $content);
        $this->assertStringContainsString('<link rel="alternate" hreflang="ru" href="'.route('landing.ru.geo', ['landingGeoSlug' => 'boryspil']).'">', $content);
        $response->assertSeeText('Натяжные потолки в Борисполе');
        $response->assertSeeText('Преимущества');
        $response->assertSeeText('Заказать звонок');
    }

    public function test_it_does_not_expose_an_uk_prefixed_boryspil_route(): void
    {
        $this->get('/uk/boryspil')->assertNotFound();
    }

    public function test_it_does_not_publish_an_unregistered_complex_landing(): void
    {
        $this->get('/boryspil/unregistered-complex')->assertNotFound();
        $this->get('/ru/boryspil/unregistered-complex')->assertNotFound();
    }

    public function test_it_renders_registered_complex_content_in_both_locales(): void
    {
        config()->set('landing_pages.areas.boryspil.complexes', ['sample']);

        Lang::addLines([
            'landing/pages/boryspil/sample.geo.h1' => 'Тестовий ЖК у Бориспільському кластері',
            'landing/pages/boryspil/sample.faq.local_items' => [
                ['question' => 'Питання про тестовий ЖК?', 'answer' => 'Відповідь про тестовий ЖК.'],
            ],
        ], 'uk');
        Lang::addLines([
            'landing/pages/boryspil/sample.geo.h1' => 'Тестовый ЖК в Бориспольском кластере',
            'landing/pages/boryspil/sample.faq.local_items' => [
                ['question' => 'Вопрос о тестовом ЖК?', 'answer' => 'Ответ о тестовом ЖК.'],
            ],
        ], 'ru');

        $uk = $this->get('/boryspil/sample');
        $uk->assertOk();
        $uk->assertSeeText('Тестовий ЖК у Бориспільському кластері');
        $uk->assertSeeText('Питання про тестовий ЖК?');
        $uk->assertSeeText('Натяжні стелі в Борисполі під ключ | Добрі стелі');

        $ru = $this->get('/ru/boryspil/sample');
        $ru->assertOk();
        $ru->assertSeeText('Тестовый ЖК в Бориспольском кластере');
        $ru->assertSeeText('Вопрос о тестовом ЖК?');
        $ru->assertSeeText('Натяжные потолки в Борисполе под ключ | Добрі стелі');
    }

    public function test_it_keeps_root_landing_metadata_and_hero_content_unchanged(): void
    {
        $response = $this->get('/');

        $response->assertOk();

        $content = $response->getContent();

        $this->assertStringContainsString('<title>Натяжні стелі в Києві та області під ключ | Добрі стелі</title>', $content);
        $this->assertStringContainsString('<link rel="canonical" href="'.route('landing').'">', $content);
        $this->assertStringContainsString('<meta property="og:url" content="'.route('landing').'">', $content);
        $this->assertStringContainsString('Натяжні стелі в Києві та області', $content);
        $this->assertStringContainsString('alt="Натяжна стеля з підсвіткою в сучасному інтер’єрі, Київ"', $content);
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
