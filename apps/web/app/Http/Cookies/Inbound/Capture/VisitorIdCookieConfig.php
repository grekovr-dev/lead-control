<?php

namespace App\Http\Cookies\Inbound\Capture;

final readonly class VisitorIdCookieConfig
{
    public function __construct(
        private string $cookieName = 'inbound_visitor_id',
        private int $lifetimeDays = 30,
    ) {}

    public function cookieName(): string
    {
        return $this->cookieName;
    }

    public function lifetimeDays(): int
    {
        return $this->lifetimeDays;
    }
}
