<?php

namespace App\Http\Cookies\Inbound\Capture;

use DateTimeImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

final class ReferrerCookieStore
{
    public function __construct(
        private string $cookieName = 'inbound_referrer',
        private int $lifetimeMinutes = 43200,
    ) {
    }

    public function cookieName(): string
    {
        return $this->cookieName;
    }

    public function resolve(Request $request): ?string
    {
        $referrer = $request->cookie($this->cookieName);

        if (!is_string($referrer)) {
            return null;
        }

        $referrer = trim($referrer);

        return $referrer === '' ? null : $referrer;
    }

    public function make(string $referrer): Cookie
    {
        $referrer = trim($referrer);

        return Cookie::create(
            $this->cookieName,
            $referrer,
            new DateTimeImmutable(sprintf('+%d minutes', $this->lifetimeMinutes)),
            '/',
            null,
            false,
            true,
            false,
            Cookie::SAMESITE_LAX,
        );
    }
}
