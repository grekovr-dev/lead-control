<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http\Cookies\Inbound\Capture;

use App\Http\Cookies\Inbound\Capture\AttributionCookieStore;
use Illuminate\Http\Request;
use Inbound\Domain\Shared\Attribution;
use PHPUnit\Framework\TestCase;

final class AttributionCookieStoreTest extends TestCase
{
    public function test_it_resolves_attribution_from_cookie_snapshot(): void
    {
        $store = new AttributionCookieStore;
        $request = Request::create('/', 'GET', [], [
            $store->cookieName() => json_encode([
                'source' => 'google',
                'medium' => 'cpc',
                'campaign' => 'spring-sale',
                'content' => 'hero-banner',
                'term' => 'stretch ceiling',
                'gclid' => 'gclid-1',
                'fbclid' => 'fbclid-1',
                'msclkid' => 'msclkid-1',
                'referrer' => 'https://google.com/search?q=stretch+ceiling',
            ], JSON_THROW_ON_ERROR),
        ]);

        $result = $store->resolve($request);

        $this->assertSame('google', $result->source());
        $this->assertSame('cpc', $result->medium());
        $this->assertSame('spring-sale', $result->campaign());
        $this->assertSame('hero-banner', $result->content());
        $this->assertSame('stretch ceiling', $result->term());
        $this->assertSame('gclid-1', $result->gclid());
        $this->assertSame('fbclid-1', $result->fbclid());
        $this->assertSame('msclkid-1', $result->msclkid());
        $this->assertSame('https://google.com/search?q=stretch+ceiling', $result->referrer());
    }

    public function test_it_returns_empty_attribution_when_cookie_is_missing_or_invalid(): void
    {
        $store = new AttributionCookieStore;

        $missingCookieRequest = Request::create('/');
        $invalidCookieRequest = Request::create('/', 'GET', [], [
            $store->cookieName() => '{',
        ]);

        $this->assertTrue($store->resolve($missingCookieRequest)->isEmpty());
        $this->assertTrue($store->resolve($invalidCookieRequest)->isEmpty());
    }

    public function test_it_creates_cookie_with_serialized_attribution_snapshot(): void
    {
        $store = new AttributionCookieStore;
        $attribution = new Attribution(
            'google',
            'cpc',
            'spring-sale',
            'hero-banner',
            'stretch ceiling',
            'gclid-1',
            'fbclid-1',
            'msclkid-1',
            'https://google.com/search?q=stretch+ceiling',
        );

        $cookie = $store->make($attribution);

        $this->assertSame($store->cookieName(), $cookie->getName());
        $this->assertSame('/', $cookie->getPath());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', strtolower((string) $cookie->getSameSite()));
        $this->assertSame($attribution->toArray(), json_decode((string) $cookie->getValue(), true, 512, JSON_THROW_ON_ERROR));
    }

    public function test_it_creates_expired_cookie_for_forget(): void
    {
        $store = new AttributionCookieStore;

        $cookie = $store->forget();

        $this->assertSame($store->cookieName(), $cookie->getName());
        $this->assertSame('', $cookie->getValue());
        $this->assertLessThan(time(), $cookie->getExpiresTime());
        $this->assertSame('/', $cookie->getPath());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', strtolower((string) $cookie->getSameSite()));
    }
}
