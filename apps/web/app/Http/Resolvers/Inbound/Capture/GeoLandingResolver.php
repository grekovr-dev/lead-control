<?php

declare(strict_types=1);

namespace App\Http\Resolvers\Inbound\Capture;

use Illuminate\Support\Facades\Route;

final class GeoLandingResolver
{
    public function resolve(?string $slug): GeoLandingContext
    {
        $normalizedSlug = $this->normalizeSlug($slug);

        return $this->contexts()[$normalizedSlug ?? 'default'] ?? $this->contexts()['default'];
    }

    /**
     * @return array<string, GeoLandingContext>
     */
    private function contexts(): array
    {
        return [
            'default' => new GeoLandingContext(
                slug: null,
                cityName: 'Київ та область',
                title: 'Натяжні стелі в Києві та області під ключ | Добрі стелі',
                description: 'Безкоштовний замір, прозорий прорахунок і монтаж натяжних стель у Києві та області. Працюємо швидко та якісно.',
                canonicalUrl: Route::has('landing') ? route('landing') : url('/'),
                h1: 'Натяжні стелі в Києві та області',
                leadSentence: 'Швидкий виїзд на замір у Києві та області у зручний для вас час.',
                ogImageAlt: 'Натяжні стелі в Києві та області',
                schemaName: 'Натяжні стелі в Києві та області',
                schemaDescription: 'Монтаж натяжних стель у Києві та області з безкоштовним заміром і попереднім прорахунком вартості.',
                areaServed: [
                    'Київ',
                    'Київська область',
                ],
            ),
            'boryspil' => new GeoLandingContext(
                slug: 'boryspil',
                cityName: 'Бориспіль',
                title: 'Натяжні стелі в Борисполі під ключ | Добрі стелі',
                description: 'Безкоштовний замір, прозорий прорахунок і монтаж натяжних стель у Борисполі та районі. Працюємо швидко та якісно.',
                canonicalUrl: Route::has('landing.geo')
                    ? route('landing.geo', ['landingGeoSlug' => 'boryspil'])
                    : url('/boryspil'),
                h1: 'Натяжні стелі в Борисполі',
                leadSentence: 'Швидкий виїзд на замір у Борисполі у зручний для вас час.',
                ogImageAlt: 'Натяжні стелі в Борисполі',
                schemaName: 'Натяжні стелі в Борисполі',
                schemaDescription: 'Монтаж натяжних стель у Борисполі з безкоштовним заміром і попереднім прорахунком вартості.',
                areaServed: [
                    'Бориспіль',
                    'Київська область',
                ],
            ),
        ];
    }

    private function normalizeSlug(?string $slug): ?string
    {
        if (! is_string($slug)) {
            return null;
        }

        $slug = trim(mb_strtolower($slug));

        return $slug !== '' ? $slug : null;
    }
}
