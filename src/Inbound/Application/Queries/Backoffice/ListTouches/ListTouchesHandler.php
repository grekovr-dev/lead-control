<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListTouches;

final readonly class ListTouchesHandler
{
    public function __construct(
        private TouchesListReadModel $readModel,
    ) {
    }

    public function __invoke(ListTouchesQuery $query): TouchesListView
    {
        return ($this->readModel)($query);
    }
}
