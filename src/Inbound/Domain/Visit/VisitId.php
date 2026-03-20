<?php

declare(strict_types=1);

namespace Inbound\Domain\Visit;

use InvalidArgumentException;

final readonly class VisitId
{
    private string $value;

    public function __construct(
        string $value,
    ) {
        $value = trim($value);

        if ($value === '') {
            throw new InvalidArgumentException('VisitId cannot be empty.');
        }

        $this->value = $value;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
