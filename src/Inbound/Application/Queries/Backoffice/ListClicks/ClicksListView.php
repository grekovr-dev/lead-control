<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListClicks;

/**
 * @phpstan-type ClickListItemList list<ClickListItemView>
 */
final readonly class ClicksListView
{
    /**
     * @param ClickListItemList $items
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
