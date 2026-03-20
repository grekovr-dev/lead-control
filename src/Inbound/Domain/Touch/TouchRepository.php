<?php

declare(strict_types=1);

namespace Inbound\Domain\Touch;

interface TouchRepository
{
    public function save(Touch $touch): void;

    public function findById(TouchId $id): ?Touch;
}
