<?php

declare(strict_types=1);

namespace App\Http\Resolvers\Inbound\Capture;

final class RefererAttributionMapper
{
    /**
     * @var array<int, array{
     *     patterns: list<string>,
     *     source: string,
     *     medium: string
     * }>
     */
    private const MAP = [
        [
            'patterns' => ['google.', 'google.com'],
            'source' => 'google',
            'medium' => 'organic',
        ],
        [
            'patterns' => ['bing.com'],
            'source' => 'bing',
            'medium' => 'organic',
        ],
        [
            'patterns' => ['search.yahoo.com', 'yahoo.'],
            'source' => 'yahoo',
            'medium' => 'organic',
        ],
        [
            'patterns' => ['duckduckgo.com'],
            'source' => 'duckduckgo',
            'medium' => 'organic',
        ],
        [
            'patterns' => ['search.brave.com'],
            'source' => 'brave',
            'medium' => 'organic',
        ],
        [
            'patterns' => ['yandex.'],
            'source' => 'yandex',
            'medium' => 'organic',
        ],
        [
            'patterns' => ['meta.ua'],
            'source' => 'meta',
            'medium' => 'organic',
        ],
        [
            'patterns' => ['facebook.com', 'm.facebook.com', 'l.facebook.com', 'lm.facebook.com'],
            'source' => 'facebook',
            'medium' => 'social',
        ],
        [
            'patterns' => ['instagram.com', 'l.instagram.com'],
            'source' => 'instagram',
            'medium' => 'social',
        ],
        [
            'patterns' => ['t.co', 'twitter.com', 'x.com'],
            'source' => 'twitter',
            'medium' => 'social',
        ],
        [
            'patterns' => ['linkedin.com', 'lnkd.in'],
            'source' => 'linkedin',
            'medium' => 'social',
        ],
        [
            'patterns' => ['tiktok.com'],
            'source' => 'tiktok',
            'medium' => 'social',
        ],
        [
            'patterns' => ['youtube.com', 'youtu.be'],
            'source' => 'youtube',
            'medium' => 'social',
        ],
        [
            'patterns' => ['telegram.me', 't.me', 'web.telegram.org'],
            'source' => 'telegram',
            'medium' => 'social',
        ],
        [
            'patterns' => ['reddit.com', 'out.reddit.com'],
            'source' => 'reddit',
            'medium' => 'social',
        ],
        [
            'patterns' => ['pinterest.com'],
            'source' => 'pinterest',
            'medium' => 'social',
        ],
        [
            'patterns' => ['whatsapp.com', 'web.whatsapp.com'],
            'source' => 'whatsapp',
            'medium' => 'messenger',
        ],
        [
            'patterns' => ['mail.google.com'],
            'source' => 'gmail',
            'medium' => 'email',
        ],
        [
            'patterns' => ['outlook.live.com', 'outlook.office.com'],
            'source' => 'outlook',
            'medium' => 'email',
        ],
        [
            'patterns' => ['mail.yahoo.com'],
            'source' => 'yahoo_mail',
            'medium' => 'email',
        ],
        [
            'patterns' => ['mail.ukr.net'],
            'source' => 'ukrnet_mail',
            'medium' => 'email',
        ],
        [
            'patterns' => ['googleads.g.doubleclick.net', 'googleadservices.com', 'doubleclick.net'],
            'source' => 'google',
            'medium' => 'cpc',
        ],
    ];

    /**
     * @param  list<string>  $internalHosts
     * @return array{source: string, medium: string}|null
     */
    public function resolveFromReferer(?string $referer, array $internalHosts = []): ?array
    {
        $host = $this->extractHost($referer);

        if ($host === null) {
            return null;
        }

        if ($this->isInternalHost($host, $internalHosts)) {
            return null;
        }

        foreach (self::MAP as $rule) {
            foreach ($rule['patterns'] as $pattern) {
                if ($this->hostMatches($host, $pattern)) {
                    return [
                        'source' => $rule['source'],
                        'medium' => $rule['medium'],
                    ];
                }
            }
        }

        return [
            'source' => $this->fallbackSourceFromHost($host),
            'medium' => 'referral',
        ];
    }

    private function extractHost(?string $referer): ?string
    {
        if ($referer === null || trim($referer) === '') {
            return null;
        }

        $host = parse_url($referer, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return null;
        }

        $host = strtolower($host);

        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }

        return $host !== '' ? $host : null;
    }

    /**
     * @param  list<string>  $internalHosts
     */
    private function isInternalHost(string $host, array $internalHosts): bool
    {
        foreach ($internalHosts as $internalHost) {
            $normalized = strtolower(trim($internalHost));

            if ($normalized === '') {
                continue;
            }

            if (str_starts_with($normalized, 'www.')) {
                $normalized = substr($normalized, 4);
            }

            if ($host === $normalized) {
                return true;
            }

            if (str_ends_with($host, '.'.$normalized)) {
                return true;
            }
        }

        return false;
    }

    private function hostMatches(string $host, string $pattern): bool
    {
        $pattern = strtolower($pattern);

        if ($host === $pattern) {
            return true;
        }

        return str_contains($host, $pattern);
    }

    private function fallbackSourceFromHost(string $host): string
    {
        $parts = explode('.', $host);

        if (count($parts) >= 2) {
            return $parts[count($parts) - 2].'.'.$parts[count($parts) - 1];
        }

        return $host;
    }
}
