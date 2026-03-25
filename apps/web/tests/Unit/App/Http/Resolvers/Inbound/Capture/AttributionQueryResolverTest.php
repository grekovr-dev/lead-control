<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http\Resolvers\Inbound\Capture;

use App\Http\Resolvers\Inbound\Capture\AttributionQueryResolver;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

final class AttributionQueryResolverTest extends TestCase
{
    public function test_it_resolves_attribution_from_request_query(): void
    {
        $resolver = new AttributionQueryResolver();
        $request = Request::create('/', 'GET', [
            'utm_source' => ' google ',
            'utm_medium' => ' cpc ',
            'utm_campaign' => ' spring-sale ',
            'utm_content' => ' hero-banner ',
            'utm_term' => ' stretch ceiling ',
            'gclid' => ' gclid-1 ',
            'fbclid' => ' fbclid-1 ',
            'msclkid' => ' msclkid-1 ',
        ]);

        $result = $resolver->resolve($request);

        $this->assertSame('google', $result->source());
        $this->assertSame('cpc', $result->medium());
        $this->assertSame('spring-sale', $result->campaign());
        $this->assertSame('hero-banner', $result->content());
        $this->assertSame('stretch ceiling', $result->term());
        $this->assertSame('gclid-1', $result->gclid());
        $this->assertSame('fbclid-1', $result->fbclid());
        $this->assertSame('msclkid-1', $result->msclkid());
    }

    public function test_it_returns_empty_attribution_when_query_params_are_missing(): void
    {
        $resolver = new AttributionQueryResolver();
        $request = Request::create('/');

        $result = $resolver->resolve($request);

        $this->assertTrue($result->isEmpty());
    }

    public function test_it_ignores_non_string_values_and_normalizes_blank_strings(): void
    {
        $resolver = new AttributionQueryResolver();
        $request = Request::create('/', 'GET', [
            'utm_source' => '   ',
            'utm_medium' => ['cpc'],
            'fbclid' => ' fbclid-1 ',
        ]);

        $result = $resolver->resolve($request);

        $this->assertNull($result->source());
        $this->assertNull($result->medium());
        $this->assertSame('fbclid-1', $result->fbclid());
    }
}
