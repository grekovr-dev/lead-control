<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http\Resolvers\Inbound\Capture;

use App\Http\Cookies\Inbound\Capture\VisitorIdCookieConfig;
use App\Http\Resolvers\Inbound\Capture\VisitorIdResolver;
use Illuminate\Http\Request;
use Inbound\Domain\Shared\VisitorId;
use PHPUnit\Framework\TestCase;

final class VisitorIdResolverTest extends TestCase
{
    public function test_it_resolves_existing_valid_visitor_id_from_cookie(): void
    {
        $config = new VisitorIdCookieConfig('custom_visitor_cookie');
        $resolver = new VisitorIdResolver($config);
        $request = Request::create('/', 'GET', [], [
            $config->cookieName() => '550e8400-e29b-41d4-a716-446655440000',
        ]);

        $result = $resolver->resolve($request);

        $this->assertInstanceOf(VisitorId::class, $result);
        $this->assertSame('550e8400-e29b-41d4-a716-446655440000', $result?->value());
    }

    public function test_it_returns_null_when_cookie_is_missing(): void
    {
        $resolver = new VisitorIdResolver(new VisitorIdCookieConfig);
        $request = Request::create('/');

        $result = $resolver->resolve($request);

        $this->assertNull($result);
    }

    public function test_it_returns_null_when_cookie_is_invalid(): void
    {
        $resolver = new VisitorIdResolver(new VisitorIdCookieConfig);
        $request = Request::create('/', 'GET', [], [
            'inbound_visitor_id' => 'not-a-uuid',
        ]);

        $result = $resolver->resolve($request);

        $this->assertNull($result);
    }
}
