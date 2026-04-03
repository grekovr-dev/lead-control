<?php

declare(strict_types=1);

namespace Inbound\Domain\LeadStatusHistory;

use Inbound\Domain\Lead\LeadId;

interface LeadStatusTransitionRepository
{
    public function save(LeadStatusTransition $transition): void;

    /**
     * @return list<LeadStatusTransition>
     */
    public function findByLeadId(LeadId $leadId): array;
}
