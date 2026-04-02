<?php

namespace App\Http\Resolvers\Inbound\Capture;

use App\Http\Cookies\Inbound\Capture\VisitorIdCookieConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inbound\Domain\Shared\VisitorId;

final class VisitorIdResolver
{
    public function __construct(
        private VisitorIdCookieConfig $config,
    ) {}

    public function resolve(Request $request): ?VisitorId
    {
        $cookieValue = $request->cookie($this->config->cookieName());

        if (! is_string($cookieValue)) {
            return null;
        }

        $cookieValue = trim($cookieValue);

        if (! Str::isUuid($cookieValue)) {
            return null;
        }

        return new VisitorId($cookieValue);
    }
}
