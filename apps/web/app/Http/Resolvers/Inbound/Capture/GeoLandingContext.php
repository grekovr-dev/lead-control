<?php

declare(strict_types=1);

namespace App\Http\Resolvers\Inbound\Capture;

/**
 * @phpstan-type GeoLandingAreaServed array<int, string>
 */
final readonly class GeoLandingContext
{
    /**
     * @param  GeoLandingAreaServed  $areaServed
     */
    public function __construct(
        public ?string $slug,
        public string $cityName,
        public string $title,
        public string $description,
        public string $h1,
        public string $leadSentence,
        public string $ogImageAlt,
        public string $schemaName,
        public string $schemaDescription,
        public array $areaServed,
    ) {}
}
