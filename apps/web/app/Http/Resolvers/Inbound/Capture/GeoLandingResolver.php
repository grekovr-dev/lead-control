<?php

declare(strict_types=1);

namespace App\Http\Resolvers\Inbound\Capture;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GeoLandingResolver
{
    public function __construct(private LandingContentResolver $contentResolver) {}

    public function resolve(?string $slug, string $locale, ?string $complexSlug = null): GeoLandingContext
    {
        $normalizedSlug = $this->normalizeSlug($slug);
        $areaSlug = $normalizedSlug;
        $complexSlug = $this->normalizeSlug($complexSlug);
        $areas = config('landing_pages.areas', []);

        if ($areaSlug !== null && ! array_key_exists($areaSlug, $areas)) {
            throw new NotFoundHttpException;
        }

        if ($complexSlug !== null && ($areaSlug === null
            || ! in_array($complexSlug, $areas[$areaSlug]['complexes'] ?? [], true))) {
            throw new NotFoundHttpException;
        }

        $copy = $this->contentResolver->resolve($locale, $areaSlug, $complexSlug);
        $geo = $copy['geo'];

        return new GeoLandingContext(
            locale: $locale,
            slug: $areaSlug === null ? null : implode('/', array_filter([$areaSlug, $complexSlug])),
            cityName: $geo['city_name'],
            title: $geo['title'],
            description: $geo['description'],
            canonicalUrl: $this->urlFor($areaSlug, $complexSlug, $locale),
            h1: $geo['h1'],
            leadSentence: $geo['lead_sentence'],
            ogImageAlt: $geo['og_image_alt'],
            schemaName: $geo['schema_name'],
            schemaDescription: $geo['schema_description'],
            areaServed: $geo['area_served'],
            alternateUrls: [
                'uk' => $this->urlFor($areaSlug, $complexSlug, 'uk'),
                'ru' => $this->urlFor($areaSlug, $complexSlug, 'ru'),
            ],
        );
    }

    private function urlFor(?string $areaSlug, ?string $complexSlug, string $locale): string
    {
        if ($complexSlug !== null) {
            return route($locale === 'uk' ? 'landing.complex' : 'landing.ru.complex', [
                'landingGeoSlug' => $areaSlug,
                'landingComplexSlug' => $complexSlug,
            ]);
        }

        if ($locale === 'uk') {
            return $areaSlug === null
                ? route('landing')
                : route('landing.geo', ['landingGeoSlug' => $areaSlug]);
        }

        return $areaSlug === null
            ? route('landing.ru')
            : route('landing.ru.geo', ['landingGeoSlug' => $areaSlug]);
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
