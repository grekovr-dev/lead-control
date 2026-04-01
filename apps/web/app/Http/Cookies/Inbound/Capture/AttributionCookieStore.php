<?php

namespace App\Http\Cookies\Inbound\Capture;

use DateTimeImmutable;
use Illuminate\Http\Request;
use Inbound\Domain\Shared\Attribution;
use JsonException;
use Symfony\Component\HttpFoundation\Cookie;

final class AttributionCookieStore
{
    public function __construct(
        private string $cookieName = 'inbound_attribution',
        private int $lifetimeMinutes = 43200,
    ) {}

    public function cookieName(): string
    {
        return $this->cookieName;
    }

    public function resolve(Request $request): Attribution
    {
        $payload = $request->cookie($this->cookieName);

        if (! is_string($payload) || trim($payload) === '') {
            return Attribution::empty();
        }

        try {
            $data = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return Attribution::empty();
        }

        if (! is_array($data)) {
            return Attribution::empty();
        }

        return new Attribution(
            $this->resolveString($data, 'source'),
            $this->resolveString($data, 'medium'),
            $this->resolveString($data, 'campaign'),
            $this->resolveString($data, 'content'),
            $this->resolveString($data, 'term'),
            $this->resolveString($data, 'gclid'),
            $this->resolveString($data, 'fbclid'),
            $this->resolveString($data, 'msclkid'),
            $this->resolveString($data, 'referrer'),
        );
    }

    public function make(Attribution $attribution): Cookie
    {
        try {
            $payload = json_encode($attribution->toArray(), JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $payload = '{}';
        }

        return Cookie::create(
            $this->cookieName,
            $payload,
            new DateTimeImmutable(sprintf('+%d minutes', $this->lifetimeMinutes)),
            '/',
            null,
            false,
            true,
            false,
            Cookie::SAMESITE_LAX,
        );
    }

    public function forget(): Cookie
    {
        return Cookie::create(
            $this->cookieName,
            '',
            new DateTimeImmutable('-1 day'),
            '/',
            null,
            false,
            true,
            false,
            Cookie::SAMESITE_LAX,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveString(array $data, string $key): ?string
    {
        $value = $data[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
