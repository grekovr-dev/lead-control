<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadDetails;

final readonly class AttributionSnapshotView
{
    public function __construct(
        public ?string $source,
        public ?string $medium,
        public ?string $campaign,
        public ?string $content,
        public ?string $term,
        public ?string $gclid,
        public ?string $fbclid,
        public ?string $msclkid,
    ) {
    }
}
