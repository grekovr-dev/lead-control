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
        $resolver = $this->app->make(GeoLandingResolver::class);

        $context = $resolver->resolve(null, 'uk');

        $this->assertSame('uk', $context->locale);
        $this->assertNull($context->slug);
        $this->assertSame('Київ та область', $context->cityName);
        $this->assertSame('Натяжні стелі в Києві та області під ключ | Добрі стелі', $context->title);
        $this->assertSame('Натяжні стелі в Києві та області', $context->h1);
        $this->assertSame(['Київ', 'Київська область'], $context->areaServed);
    }

    #[Test]
    public function it_resolves_the_boryspil_geo_landing_context(): void
    {
        $resolver = $this->app->make(GeoLandingResolver::class);

        $context = $resolver->resolve('boryspil', 'uk');

        $this->assertSame('boryspil', $context->slug);
        $this->assertSame('Бориспіль', $context->cityName);
        $this->assertSame('Натяжні стелі в Борисполі під ключ | Добрі стелі', $context->title);
        $this->assertSame('Натяжні стелі в Борисполі', $context->h1);
        $this->assertSame(['Бориспіль', 'Київська область'], $context->areaServed);
    }

    #[Test]
    public function it_resolves_a_russian_context_with_only_the_ru_url_prefix(): void
    {
        $resolver = $this->app->make(GeoLandingResolver::class);

        $context = $resolver->resolve('boryspil', 'ru');

        $this->assertSame('ru', $context->locale);
        $this->assertSame('Борисполь', $context->cityName);
        $this->assertSame('Натяжные потолки в Борисполе под ключ | Добрі стелі', $context->title);
        $this->assertSame(route('landing.ru.geo', ['landingGeoSlug' => 'boryspil']), $context->canonicalUrl);
        $this->assertSame(route('landing.geo', ['landingGeoSlug' => 'boryspil']), $context->alternateUrls['uk']);
        $this->assertSame(route('landing.ru.geo', ['landingGeoSlug' => 'boryspil']), $context->alternateUrls['ru']);
    }
}
