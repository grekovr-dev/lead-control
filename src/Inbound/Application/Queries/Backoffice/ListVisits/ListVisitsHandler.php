<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListVisits;

final readonly class ListVisitsHandler
{
    public function __construct(
        private VisitsListReadModel $readModel,
    ) {
    }

    public function __invoke(ListVisitsQuery $query): VisitsListView
    {
        return ($this->readModel)($query);
    }
}
