<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Response;

final class SitemapController extends Controller
{
    public function __invoke(): Response|View
    {
        $landingUrls = [route('landing'), route('landing.ru')];

        foreach (config('landing_pages.areas', []) as $areaSlug => $area) {
            $landingUrls[] = route('landing.geo', ['landingGeoSlug' => $areaSlug]);
            $landingUrls[] = route('landing.ru.geo', ['landingGeoSlug' => $areaSlug]);

            foreach ($area['complexes'] ?? [] as $complexSlug) {
                $parameters = [
                    'landingGeoSlug' => $areaSlug,
                    'landingComplexSlug' => $complexSlug,
                ];
                $landingUrls[] = route('landing.complex', $parameters);
                $landingUrls[] = route('landing.ru.complex', $parameters);
            }
        }

        return response()
            ->view('sitemap', [
                'landingUrls' => $landingUrls,
            ])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }
}
