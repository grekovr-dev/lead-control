<?php

declare(strict_types=1);

namespace Inbound\Domain\LeadNote;

use Inbound\Domain\Lead\LeadId;

interface LeadNoteRepository
{
    public function save(LeadNote $leadNote): void;

    /**
     * @return list<LeadNote>
     */
    public function findByLeadId(LeadId $leadId): array;
}
