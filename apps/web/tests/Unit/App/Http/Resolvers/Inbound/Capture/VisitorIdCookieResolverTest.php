<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http\Resolvers\Inbound\Capture;

use App\Http\Resolvers\Inbound\Capture\VisitorIdCookieResolver;
use Illuminate\Http\Request;
use Inbound\Domain\Shared\VisitorId;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;

final class VisitorIdCookieResolverTest extends TestCase
{
    public function test_it_resolves_existing_valid_visitor_id_from_cookie(): void
    {
        $resolver = new VisitorIdCookieResolver();
        $request = Request::create('/', 'GET', [], [
            $resolver->cookieName() => '550e8400-e29b-41d4-a716-446655440000',
        ]);

        $result = $resolver->resolve($request);

        $this->assertInstanceOf(VisitorId::class, $result);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $result->value());
    }

    public function test_it_generates_new_visitor_id_when_cookie_is_missing(): void
    {
        $resolver = new VisitorIdCookieResolver();
        $request = Request::create('/');

        $result = $resolver->resolve($request);

        $this->assertInstanceOf(VisitorId::class, $result);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $result->value(),
        );
    }

    public function test_it_generates_new_visitor_id_when_cookie_is_invalid(): void
    {
        $resolver = new VisitorIdCookieResolver();
        $request = Request::create('/', 'GET', [], [
            $resolver->cookieName() => '   ',
        ]);

        $result = $resolver->resolve($request);

        $this->assertInstanceOf(VisitorId::class, $result);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $result->value(),
        );
    }

    public function test_it_creates_visitor_cookie(): void
    {
        $resolver = new VisitorIdCookieResolver();
        $visitorId = new VisitorId('550e8400-e29b-41d4-a716-446655440000');

        $cookie = $resolver->make($visitorId);

        $this->assertInstanceOf(Cookie::class, $cookie);
        $this->assertSame($resolver->cookieName(), $cookie->getName());
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $cookie->getValue());
        $this->assertSame('/', $cookie->getPath());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertSame('lax', strtolower((string) $cookie->getSameSite()));
    }
}
