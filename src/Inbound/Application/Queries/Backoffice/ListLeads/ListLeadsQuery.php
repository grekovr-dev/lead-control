<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListLeads;

use Inbound\Domain\Lead\LeadStatus;

final readonly class ListLeadsQuery
{
    public function __construct(
        public ?LeadStatus $status = null,
        public ?string $origin = null,
        public ?string $attributionSource = null,
        public ?string $attributionMedium = null,
        public int $page = 1,
        public int $perPage = 20,
    ) {
    }
}
