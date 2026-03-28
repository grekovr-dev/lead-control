<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListTouches;

/**
 * @phpstan-type TouchListItemList list<TouchListItemView>
 */
final readonly class TouchesListView
{
    /**
     * @param TouchListItemList $items
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
