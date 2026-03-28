<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListVisits;

/**
 * @phpstan-type VisitListItemList list<VisitListItemView>
 */
final readonly class VisitsListView
{
    /**
     * @param VisitListItemList $items
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
