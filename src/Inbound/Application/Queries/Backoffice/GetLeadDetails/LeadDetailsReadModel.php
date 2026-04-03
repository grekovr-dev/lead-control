<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadDetails;

interface LeadDetailsReadModel
{
    /**
     * @throws LeadDetailsNotFoundException
     */
    public function __invoke(GetLeadDetailsQuery $query): LeadDetailsView;
}
