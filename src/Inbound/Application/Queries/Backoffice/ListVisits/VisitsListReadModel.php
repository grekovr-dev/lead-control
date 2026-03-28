<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListVisits;

interface VisitsListReadModel
{
    public function __invoke(ListVisitsQuery $query): VisitsListView;
}
