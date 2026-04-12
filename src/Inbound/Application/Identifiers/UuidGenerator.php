<?php

declare(strict_types=1);

namespace Inbound\Application\Identifiers;

interface UuidGenerator
{
    public function generate(): string;
}
