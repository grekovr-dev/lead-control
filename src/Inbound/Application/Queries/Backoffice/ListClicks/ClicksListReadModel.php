<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListClicks;

interface ClicksListReadModel
{
    public function __invoke(ListClicksQuery $query): ClicksListView;
}
