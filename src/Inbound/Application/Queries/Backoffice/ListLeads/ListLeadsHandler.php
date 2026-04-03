<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\ListLeads;

final readonly class ListLeadsHandler
{
    public function __construct(
        private LeadsListReadModel $readModel,
    ) {
    }

    public function __invoke(ListLeadsQuery $query): LeadsListView
    {
        return ($this->readModel)($query);
    }
}
