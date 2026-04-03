<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Backoffice\GetLeadDetails;

use Inbound\Domain\Lead\LeadId;

final readonly class GetLeadDetailsQuery
{
    public function __construct(
        public LeadId $leadId,
    ) {
    }
}
