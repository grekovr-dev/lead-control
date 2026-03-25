<?php

namespace App\Http\Controllers\Inbound\Capture;

use App\Http\Controllers\Controller;
use App\Http\Cookies\Inbound\Capture\AttributionCookieStore;
use App\Http\Cookies\Inbound\Capture\ReferrerCookieStore;
use App\Http\Resolvers\Inbound\Capture\AttributionQueryResolver;
use App\Http\Resolvers\Inbound\Capture\ReferrerResolver;
use App\Http\Resolvers\Inbound\Capture\VisitorIdCookieResolver;
use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function __construct(
        private VisitorIdCookieResolver $visitorIdCookieResolver,
        private AttributionQueryResolver $attributionQueryResolver,
        private AttributionCookieStore $attributionCookieStore,
        private ReferrerResolver $referrerResolver,
        private ReferrerCookieStore $referrerCookieStore,
    ) {
    }

    public function __invoke(Request $request)
    {
        $visitorId = $this->visitorIdCookieResolver->resolve($request);
        $attribution = $this->attributionQueryResolver->resolve($request);
        $referrer = $this->referrerResolver->resolve($request);

        $response = response()->view('pages.landing');
        $response->headers->setCookie($this->visitorIdCookieResolver->make($visitorId));

        if (!$attribution->isEmpty()) {
            $response->headers->setCookie($this->attributionCookieStore->make($attribution));
        }

        if ($referrer !== null) {
            $response->headers->setCookie($this->referrerCookieStore->make($referrer));
        }

        return $response;
    }
}
