<?php

declare(strict_types=1);

namespace Inbound\Domain\Visit;

use Inbound\Domain\Shared\VisitorId;

interface VisitRepository
{
    public function save(Visit $visit): void;

    public function findById(VisitId $id): ?Visit;

    public function findLastByVisitorId(VisitorId $visitorId): ?Visit;
}
