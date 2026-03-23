<?php

namespace App\Http\Resolvers\Inbound\Capture;

use Illuminate\Http\Request;

final class ReferrerResolver
{
    public function resolve(Request $request): ?string
    {
        $referrer = $request->headers->get('referer');

        if (!is_string($referrer)) {
            return null;
        }

        $referrer = trim($referrer);

        return $referrer === '' ? null : $referrer;
    }
}
