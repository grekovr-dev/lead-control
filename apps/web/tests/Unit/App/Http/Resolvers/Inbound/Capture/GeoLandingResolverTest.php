<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http\Resolvers\Inbound\Capture;

use App\Http\Resolvers\Inbound\Capture\GeoLandingResolver;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class GeoLandingResolverTest extends TestCase
{
    #[Test]
    public function it_resolves_the_default_geo_landing_context_for_the_root_landing(): void
    {
        $resolver = new GeoLandingResolver;

        $context = $resolver->resolve(null);

        $this->assertNull($context->slug);
        $this->assertSame('Київ та область', $context->cityName);
        $this->assertSame('Натяжні стелі в Києві та області під ключ | Добрі стелі', $context->title);
        $this->assertSame('Натяжні стелі в Києві та області', $context->h1);
        $this->assertSame(['Київ', 'Київська область'], $context->areaServed);
    }

    #[Test]
    public function it_resolves_the_boryspil_geo_landing_context(): void
    {
        $resolver = new GeoLandingResolver;

        $context = $resolver->resolve('boryspil');

        $this->assertSame('boryspil', $context->slug);
        $this->assertSame('Бориспіль', $context->cityName);
        $this->assertSame('Натяжні стелі в Борисполі під ключ | Добрі стелі', $context->title);
        $this->assertSame('Натяжні стелі в Борисполі', $context->h1);
        $this->assertSame(['Бориспіль', 'Київська область'], $context->areaServed);
    }
}
