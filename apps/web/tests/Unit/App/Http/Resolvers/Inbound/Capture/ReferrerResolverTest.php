<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http\Resolvers\Inbound\Capture;

use App\Http\Resolvers\Inbound\Capture\ReferrerResolver;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

final class ReferrerResolverTest extends TestCase
{
    public function test_it_resolves_referrer_from_request_headers(): void
    {
        $resolver = new ReferrerResolver();
        $request = Request::create('/');
        $request->headers->set('referer', ' https://example.com/catalog?utm_source=google ');

        $result = $resolver->resolve($request);

        $this->assertSame('https://example.com/catalog?utm_source=google', $result);
    }

    public function test_it_returns_null_when_referrer_header_is_missing(): void
    {
        $resolver = new ReferrerResolver();
        $request = Request::create('/');

        $result = $resolver->resolve($request);

        $this->assertNull($result);
    }

    public function test_it_returns_null_when_referrer_header_is_blank(): void
    {
        $resolver = new ReferrerResolver();
        $request = Request::create('/');
        $request->headers->set('referer', '   ');

        $result = $resolver->resolve($request);

        $this->assertNull($result);
    }
}
