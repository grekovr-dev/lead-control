<?php

declare(strict_types=1);

namespace Tests\Unit\App\Inbound\Capture;

use App\Inbound\Capture\VisitorIdCookieResolver;
use Illuminate\Http\Request;
use Inbound\Domain\Shared\VisitorId;
use PHPUnit\Framework\TestCase;

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
}
