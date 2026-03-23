<?php

declare(strict_types=1);

namespace Inbound\Domain\Lead;

interface LeadRepository
{
    public function save(Lead $lead): void;

    public function findById(LeadId $id): ?Lead;
}
