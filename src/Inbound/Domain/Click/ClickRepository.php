<?php

declare(strict_types=1);

namespace Inbound\Domain\Click;

interface ClickRepository
{
    public function save(Click $click): void;

    public function findById(ClickId $id): ?Click;
}
