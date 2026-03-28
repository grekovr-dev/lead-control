<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadDetails;

final readonly class GetLeadDetailsHandler
{
    public function __construct(
        private LeadDetailsReadModel $readModel,
    ) {
    }

    /**
     * @throws LeadDetailsNotFoundException
     */
    public function __invoke(GetLeadDetailsQuery $query): LeadDetailsView
    {
        return ($this->readModel)($query);
    }
}
