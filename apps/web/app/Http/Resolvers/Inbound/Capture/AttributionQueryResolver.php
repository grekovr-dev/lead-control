<?php

namespace App\Http\Resolvers\Inbound\Capture;

use Illuminate\Http\Request;
use Inbound\Domain\Shared\Attribution;

final class AttributionQueryResolver
{
    public function resolve(Request $request): Attribution
    {
        return new Attribution(
            $this->resolveString($request, 'utm_source'),
            $this->resolveString($request, 'utm_medium'),
            $this->resolveString($request, 'utm_campaign'),
            $this->resolveString($request, 'utm_content'),
            $this->resolveString($request, 'utm_term'),
            $this->resolveString($request, 'gclid'),
            $this->resolveString($request, 'fbclid'),
            $this->resolveString($request, 'msclkid'),
        );
    }

    private function resolveString(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        return is_string($value) ? $value : null;
    }
}
