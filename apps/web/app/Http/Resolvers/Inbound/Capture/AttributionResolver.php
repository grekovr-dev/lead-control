<?php

declare(strict_types=1);

namespace App\Http\Resolvers\Inbound\Capture;

use Illuminate\Http\Request;
use Inbound\Domain\Shared\Attribution;

final class AttributionResolver
{
    public function __construct(
        private RefererAttributionMapper $refererAttributionMapper,
    ) {}

    public function resolve(Request $request): Attribution
    {
        $utmSource = $this->nullable($request->query('utm_source'));
        $utmMedium = $this->nullable($request->query('utm_medium'));
        $utmCampaign = $this->nullable($request->query('utm_campaign'));
        $utmContent = $this->nullable($request->query('utm_content'));
        $utmTerm = $this->nullable($request->query('utm_term'));
        $gclid = $this->nullable($request->query('gclid'));
        $fbclid = $this->nullable($request->query('fbclid'));
        $msclkid = $this->nullable($request->query('msclkid'));
        $referer = $this->nullable($request->headers->get('referer'));

        if ($utmSource !== null || $utmMedium !== null) {
            return new Attribution(
                $utmSource ?? 'unknown',
                $utmMedium ?? 'unknown',
                $utmCampaign,
                $utmContent,
                $utmTerm,
                $gclid,
                $fbclid,
                $msclkid,
                $referer,
            );
        }

        if ($gclid !== null) {
            return new Attribution(
                'google',
                'cpc',
                $utmCampaign,
                $utmContent,
                $utmTerm,
                $gclid,
                $fbclid,
                $msclkid,
                $referer,
            );
        }

        if ($fbclid !== null) {
            return new Attribution(
                'facebook',
                'paid_social',
                $utmCampaign,
                $utmContent,
                $utmTerm,
                $gclid,
                $fbclid,
                $msclkid,
                $referer,
            );
        }

        if ($msclkid !== null) {
            return new Attribution(
                'microsoft',
                'cpc',
                $utmCampaign,
                $utmContent,
                $utmTerm,
                $gclid,
                $fbclid,
                $msclkid,
                $referer,
            );
        }

        $mappedAttribution = $this->refererAttributionMapper->resolveFromReferer(
            $referer,
            [$request->getHost()],
        );

        if ($mappedAttribution !== null) {
            return new Attribution(
                $mappedAttribution['source'],
                $mappedAttribution['medium'],
                null,
                null,
                null,
                null,
                null,
                null,
                $referer,
            );
        }

        return Attribution::direct();
    }

    private function nullable(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
