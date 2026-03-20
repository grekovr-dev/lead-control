<?php

declare(strict_types=1);

namespace Tests\Unit\Inbound\Domain\Shared;

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
        );

        $this->assertSame('google', $attribution->source());
        $this->assertSame('cpc', $attribution->medium());
        $this->assertSame('spring-sale', $attribution->campaign());
        $this->assertSame('hero-banner', $attribution->content());
        $this->assertSame('stretch ceilings', $attribution->term());
        $this->assertSame('gclid-123', $attribution->gclid());
        $this->assertNull($attribution->fbclid());
        $this->assertNull($attribution->msclkid());
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
        ], $attribution->toArray());
    }

    public function test_it_compares_by_all_values(): void
    {
        $left = new Attribution('google', 'cpc', 'spring', null, null, null, null, null);
        $same = new Attribution(' google ', ' cpc ', ' spring ', null, null, null, null, null);
        $different = new Attribution('facebook', 'cpc', 'spring', null, null, null, null, null);

        $this->assertTrue($left->equals($same));
        $this->assertFalse($left->equals($different));
    }
}
