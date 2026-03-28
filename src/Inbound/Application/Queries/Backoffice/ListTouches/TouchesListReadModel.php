<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListTouches;

interface TouchesListReadModel
{
    public function __invoke(ListTouchesQuery $query): TouchesListView;
}
