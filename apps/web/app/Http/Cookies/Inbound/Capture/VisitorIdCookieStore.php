<?php

namespace App\Http\Cookies\Inbound\Capture;

use DateTimeImmutable;
use Inbound\Domain\Shared\VisitorId;
use Symfony\Component\HttpFoundation\Cookie;

final class VisitorIdCookieStore
{
    public function __construct(
        private VisitorIdCookieConfig $config,
    ) {}

    public function cookieName(): string
    {
        return $this->config->cookieName();
    }

    public function make(VisitorId $visitorId): Cookie
    {
        return Cookie::create(
            $this->config->cookieName(),
            $visitorId->value(),
            new DateTimeImmutable(sprintf('+%d days', $this->config->lifetimeDays())),
            '/',
            null,
            false,
            true,
            false,
            Cookie::SAMESITE_LAX,
        );
    }
}
