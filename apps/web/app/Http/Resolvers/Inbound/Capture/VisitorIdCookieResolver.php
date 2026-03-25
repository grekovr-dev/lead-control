<?php

namespace App\Http\Resolvers\Inbound\Capture;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inbound\Domain\Shared\VisitorId;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Cookie;

final class VisitorIdCookieResolver
{
    public function __construct(
        private string $cookieName = 'inbound_visitor_id',
    ) {
    }

    public function cookieName(): string
    {
        return $this->cookieName;
    }

    public function resolve(Request $request): VisitorId
    {
        $cookieValue = $request->cookie($this->cookieName);

        if (is_string($cookieValue)) {
            try {
                return new VisitorId($cookieValue);
            } catch (InvalidArgumentException) {
            }
        }

        return new VisitorId((string) Str::uuid());
    }

    public function make(VisitorId $visitorId): Cookie
    {
        // TODO: Consider extracting cookie creation into a dedicated VisitorIdCookieStore
        // to mirror AttributionCookieStore and keep this resolver read-only.
        return Cookie::create(
            $this->cookieName,
            $visitorId->value(),
            now()->addDays(30),
            '/',
            null,
            false,
            true,
            false,
            Cookie::SAMESITE_LAX,
        );
    }
}
