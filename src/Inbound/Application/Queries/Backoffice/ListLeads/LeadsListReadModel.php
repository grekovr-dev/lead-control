<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListLeads;

interface LeadsListReadModel
{
    public function __invoke(ListLeadsQuery $query): LeadsListView;
}
