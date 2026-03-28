<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListClicks;

final readonly class ListClicksHandler
{
    public function __construct(
        private ClicksListReadModel $readModel,
    ) {
    }

    public function __invoke(ListClicksQuery $query): ClicksListView
    {
        return ($this->readModel)($query);
    }
}
