<?php

declare(strict_types=1);

namespace Inbound\Domain\Lead;

use Inbound\Domain\Visit\VisitId;

interface LeadRepository
{
    public function save(Lead $lead): void;

    public function findById(LeadId $id): ?Lead;

    public function findByVisitIdAndOrigin(VisitId $visitId, string $origin): ?Lead;
}
