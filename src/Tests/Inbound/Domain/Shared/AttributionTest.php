<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\Shared;

use Inbound\Domain\Shared\Attribution;
use PHPUnit\Framework\TestCase;

final class AttributionTest extends TestCase
{
    public function test_it_normalizes_values_and_exposes_them(): void
    {
        $attribution = new Attribution(
            ' google ',
            ' cpc ',
            ' spring-sale ',
            ' hero-banner ',
            ' stretch ceilings ',
            ' gclid-123 ',
            ' ',
            null,
            ' https://google.com/search?q=ceilings ',
        );

        $this->assertSame('google', $attribution->source());
        $this->assertSame('cpc', $attribution->medium());
        $this->assertSame('spring-sale', $attribution->campaign());
        $this->assertSame('hero-banner', $attribution->content());
        $this->assertSame('stretch ceilings', $attribution->term());
        $this->assertSame('gclid-123', $attribution->gclid());
        $this->assertNull($attribution->fbclid());
        $this->assertNull($attribution->msclkid());
        $this->assertSame('https://google.com/search?q=ceilings', $attribution->referrer());
        $this->assertFalse($attribution->isEmpty());
    }

    public function test_empty_factory_returns_empty_value_object(): void
    {
        $attribution = Attribution::empty();

        $this->assertTrue($attribution->isEmpty());
        $this->assertSame([
            'source' => null,
            'medium' => null,
            'campaign' => null,
            'content' => null,
            'term' => null,
            'gclid' => null,
            'fbclid' => null,
            'msclkid' => null,
            'referrer' => null,
        ], $attribution->toArray());
    }

    public function test_it_compares_by_all_values(): void
    {
        $left = new Attribution('google', 'cpc', 'spring', null, null, null, null, null, 'https://google.com');
        $same = new Attribution(' google ', ' cpc ', ' spring ', null, null, null, null, null, ' https://google.com ');
        $different = new Attribution('facebook', 'cpc', 'spring', null, null, null, null, null, 'https://google.com');

        $this->assertTrue($left->equals($same));
        $this->assertFalse($left->equals($different));
    }

    public function test_direct_factory_creates_explicit_direct_attribution(): void
    {
        $attribution = Attribution::direct();

        $this->assertSame('direct', $attribution->source());
        $this->assertSame('none', $attribution->medium());
        $this->assertNull($attribution->referrer());
    }
}
