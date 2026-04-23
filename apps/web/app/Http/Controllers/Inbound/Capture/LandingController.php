<?php

namespace App\Http\Controllers\Inbound\Capture;

use App\Http\Controllers\Controller;
use App\Http\Cookies\Inbound\Capture\AttributionCookieStore;
use App\Http\Cookies\Inbound\Capture\VisitorIdCookieStore;
use App\Http\Resolvers\Inbound\Capture\AttributionResolver;
use App\Http\Resolvers\Inbound\Capture\GeoLandingResolver;
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
    ) {}

    public function __invoke(Request $request)
    {
        $visitorId = $this->visitorIdResolver->resolve($request)
            ?? new VisitorId((string) Str::uuid());
        $attribution = $this->attributionResolver->resolve($request);
        $landingGeo = $this->geoLandingResolver->resolve($this->resolveLandingGeoSlug($request));

        $response = response()->view('pages.landing', [
            'landingGeo' => $landingGeo,
        ]);
        $response->headers->setCookie($this->visitorIdCookieStore->make($visitorId));

        if (! $attribution->isEmpty()) {
            $response->headers->setCookie($this->attributionCookieStore->make($attribution));
        }

        return $response;
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
}
