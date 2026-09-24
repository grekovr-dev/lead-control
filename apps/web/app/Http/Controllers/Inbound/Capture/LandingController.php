<?php

namespace App\Http\Controllers\Inbound\Capture;

use App\Http\Controllers\Controller;
use App\Http\Cookies\Inbound\Capture\AttributionCookieStore;
use App\Http\Cookies\Inbound\Capture\VisitorIdCookieStore;
use App\Http\Resolvers\Inbound\Capture\AttributionResolver;
use App\Http\Resolvers\Inbound\Capture\GeoLandingResolver;
use App\Http\Resolvers\Inbound\Capture\LandingContentResolver;
use App\Http\Resolvers\Inbound\Capture\VisitorIdResolver;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inbound\Domain\Shared\VisitorId;

class LandingController extends Controller
{
    public function __construct(
        private VisitorIdResolver $visitorIdResolver,
        private VisitorIdCookieStore $visitorIdCookieStore,
        private AttributionResolver $attributionResolver,
        private AttributionCookieStore $attributionCookieStore,
        private GeoLandingResolver $geoLandingResolver,
        private LandingContentResolver $landingContentResolver,
    ) {}

    public function __invoke(Request $request)
    {
        $landingLocale = $this->resolveLandingLocale($request);
        app()->setLocale($landingLocale);

        $visitorId = $this->visitorIdResolver->resolve($request)
            ?? new VisitorId((string) Str::uuid());
        $attribution = $this->attributionResolver->resolve($request);
        $landingGeo = $this->geoLandingResolver->resolve(
            $this->resolveLandingGeoSlug($request),
            $landingLocale,
            $this->resolveLandingComplexSlug($request),
        );
        $landingCopy = $this->landingContentResolver->resolve(
            $landingLocale,
            $this->resolveLandingGeoSlug($request),
            $this->resolveLandingComplexSlug($request),
        );

        $response = response()->view('pages.landing', [
            'landingGeo' => $landingGeo,
            'landingCopy' => $landingCopy,
        ]);
        $response->headers->setCookie($this->visitorIdCookieStore->make($visitorId));

        if (! $attribution->isEmpty()) {
            $response->headers->setCookie($this->attributionCookieStore->make($attribution));
        }

        return $response;
    }

    private function resolveLandingLocale(Request $request): string
    {
        $locale = $request->route('landingLocale');

        if (is_string($locale) && in_array($locale, config('app.available_locales'), true)) {
            return $locale;
        }

        return config('app.locale');
    }

    private function resolveLandingGeoSlug(Request $request): ?string
    {
        $slug = $request->route('landingGeoSlug');

        if (! is_string($slug)) {
            return null;
        }

        $slug = trim($slug);

        return $slug !== '' ? $slug : null;
    }

    private function resolveLandingComplexSlug(Request $request): ?string
    {
        $slug = $request->route('landingComplexSlug');

        return is_string($slug) && trim($slug) !== '' ? trim($slug) : null;
    }
}
