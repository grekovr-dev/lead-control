<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http\Resolvers\Inbound\Capture;

use App\Http\Resolvers\Inbound\Capture\RefererAttributionMapper;
use PHPUnit\Framework\TestCase;

final class RefererAttributionMapperTest extends TestCase
{
    public function test_it_maps_a_known_search_engine_referer(): void
    {
        $mapper = new RefererAttributionMapper();

        $result = $mapper->resolveFromReferer('https://www.google.com/search?q=stretch+ceiling');

        $this->assertSame([
            'source' => 'google',
            'medium' => 'organic',
        ], $result);
    }

    public function test_it_maps_an_unknown_external_domain_as_referral(): void
    {
        $mapper = new RefererAttributionMapper();

        $result = $mapper->resolveFromReferer('https://partner.example.org/articles/stretch-ceiling');

        $this->assertSame([
            'source' => 'example.org',
            'medium' => 'referral',
        ], $result);
    }

    public function test_it_ignores_internal_or_missing_referers(): void
    {
        $mapper = new RefererAttributionMapper();

        $this->assertNull($mapper->resolveFromReferer(null, ['example.com']));
        $this->assertNull($mapper->resolveFromReferer('https://www.example.com/catalog', ['example.com']));
    }
}
