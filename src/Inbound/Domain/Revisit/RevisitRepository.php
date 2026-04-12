<?php

declare(strict_types=1);

namespace Inbound\Domain\Revisit;

interface RevisitRepository
{
    public function save(Revisit $revisit): void;

    public function findById(RevisitId $id): ?Revisit;
}
