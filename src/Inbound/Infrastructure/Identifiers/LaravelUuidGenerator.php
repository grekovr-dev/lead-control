<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Identifiers;

use Illuminate\Support\Str;
use Inbound\Application\Identifiers\UuidGenerator;

final class LaravelUuidGenerator implements UuidGenerator
{
    public function generate(): string
    {
        return (string) Str::uuid();
    }
}
