<?php

declare(strict_types=1);

namespace App\Inbound\Capture;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inbound\Domain\Shared\VisitorId;
use InvalidArgumentException;

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
}
