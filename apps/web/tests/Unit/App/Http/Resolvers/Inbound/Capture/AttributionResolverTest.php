<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http\Resolvers\Inbound\Capture;

use App\Http\Resolvers\Inbound\Capture\AttributionResolver;
use App\Http\Resolvers\Inbound\Capture\RefererAttributionMapper;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

final class AttributionResolverTest extends TestCase
{
    public function test_it_prefers_explicit_utm_attribution(): void
    {
        $resolver = new AttributionResolver(new RefererAttributionMapper);
        $request = Request::create('/', 'GET', [
            'utm_source' => ' google ',
            'utm_medium' => ' cpc ',
            'utm_campaign' => ' spring-sale ',
        ], [], [], [
            'HTTP_REFERER' => 'https://facebook.com/ad',
            'HTTP_HOST' => 'example.com',
        ]);

        $result = $resolver->resolve($request);

        $this->assertSame('google', $result->source());
        $this->assertSame('cpc', $result->medium());
        $this->assertSame('spring-sale', $result->campaign());
        $this->assertSame('https://facebook.com/ad', $result->referrer());
    }

    public function test_it_infers_google_cpc_from_gclid(): void
    {
        $resolver = new AttributionResolver(new RefererAttributionMapper);
        $request = Request::create('/', 'GET', [
            'gclid' => 'gclid-1',
        ], [], [], [
            'HTTP_REFERER' => 'https://www.google.com/search?q=stretch+ceiling',
            'HTTP_HOST' => 'example.com',
        ]);

        $result = $resolver->resolve($request);

        $this->assertSame('google', $result->source());
        $this->assertSame('cpc', $result->medium());
        $this->assertSame('gclid-1', $result->gclid());
        $this->assertSame('https://www.google.com/search?q=stretch+ceiling', $result->referrer());
    }

    public function test_it_resolves_source_and_medium_from_external_referer_when_explicit_attribution_is_missing(): void
    {
        $resolver = new AttributionResolver(new RefererAttributionMapper);
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_REFERER' => 'https://t.me/some-channel',
            'HTTP_HOST' => 'example.com',
        ]);

        $result = $resolver->resolve($request);

        $this->assertSame('telegram', $result->source());
        $this->assertSame('social', $result->medium());
        $this->assertSame('https://t.me/some-channel', $result->referrer());
    }

    public function test_it_returns_direct_for_internal_referer_without_persisting_it(): void
    {
        $resolver = new AttributionResolver(new RefererAttributionMapper);
        $request = Request::create('/', 'GET', [], [], [], [
            'HTTP_REFERER' => 'https://www.example.com/catalog',
            'HTTP_HOST' => 'example.com',
        ]);

        $result = $resolver->resolve($request);

        $this->assertSame('direct', $result->source());
        $this->assertSame('none', $result->medium());
        $this->assertNull($result->referrer());
    }
}
