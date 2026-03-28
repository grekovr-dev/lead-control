<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListLeads;

/**
 * @phpstan-type LeadListItemList list<LeadListItemView>
 */
final readonly class LeadsListView
{
    /**
     * @param LeadListItemList $items
     */
    public function __construct(
        public int $currentPage,
        public int $perPage,
        public int $total,
        public int $lastPage,
        public array $items,
    ) {
    }
}
