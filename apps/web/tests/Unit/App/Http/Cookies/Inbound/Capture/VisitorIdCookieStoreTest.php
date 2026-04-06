<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http\Cookies\Inbound\Capture;

use App\Http\Cookies\Inbound\Capture\VisitorIdCookieConfig;
use App\Http\Cookies\Inbound\Capture\VisitorIdCookieStore;
use Inbound\Domain\Shared\VisitorId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

final class VisitorIdCookieStoreTest extends TestCase
{
    public function test_it_creates_visitor_cookie_using_config(): void
    {
        $store = new VisitorIdCookieStore(new VisitorIdCookieConfig('custom_visitor_cookie', 1));
        $visitorId = new VisitorId('550e8400-e29b-41d4-a716-446655440000');

        $cookie = $store->make($visitorId);

        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertSame($store->cookieName(), $cookie->getName());
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $cookie->getValue());
        $this->assertSame('/', $cookie->getPath());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', strtolower((string) $cookie->getSameSite()));
        $this->assertGreaterThan(time(), $cookie->getExpiresTime());
        $this->assertLessThan((new \DateTimeImmutable('+2 days'))->getTimestamp(), $cookie->getExpiresTime() + 1);
    }
}
