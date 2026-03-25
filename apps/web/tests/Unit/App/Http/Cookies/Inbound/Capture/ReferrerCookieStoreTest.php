<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http\Cookies\Inbound\Capture;

use App\Http\Cookies\Inbound\Capture\ReferrerCookieStore;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

final class ReferrerCookieStoreTest extends TestCase
{
    public function test_it_resolves_referrer_from_cookie_snapshot(): void
    {
        $store = new ReferrerCookieStore();
        $request = Request::create('/', 'GET', [], [
            $store->cookieName() => ' https://example.com/catalog?utm_source=google ',
        ]);

        $result = $store->resolve($request);

        $this->assertSame('https://example.com/catalog?utm_source=google', $result);
    }

    public function test_it_returns_null_when_cookie_is_missing_or_blank(): void
    {
        $store = new ReferrerCookieStore();

        $missingCookieRequest = Request::create('/');
        $blankCookieRequest = Request::create('/', 'GET', [], [
            $store->cookieName() => '   ',
        ]);

        $this->assertNull($store->resolve($missingCookieRequest));
        $this->assertNull($store->resolve($blankCookieRequest));
    }

    public function test_it_creates_referrer_cookie(): void
    {
        $store = new ReferrerCookieStore();

        $cookie = $store->make(' https://example.com/catalog?utm_source=google ');

        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertSame($store->cookieName(), $cookie->getName());
        $this->assertSame('https://example.com/catalog?utm_source=google', $cookie->getValue());
        $this->assertSame('/', $cookie->getPath());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', strtolower((string) $cookie->getSameSite()));
    }
}
