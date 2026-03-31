<?php

declare(strict_types=1);

namespace Inbound\Domain\Shared;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class DateRange
{
    private ?DateTimeImmutable $fromInclusive;
    private ?DateTimeImmutable $toExclusive;

    public function __construct(
        ?DateTimeImmutable $fromInclusive,
        ?DateTimeImmutable $toExclusive,
    ) {
        if ($fromInclusive === null && $toExclusive === null) {
            throw new InvalidArgumentException('DateRange requires at least one boundary.');
        }

        if ($fromInclusive !== null && $toExclusive !== null && $fromInclusive >= $toExclusive) {
            throw new InvalidArgumentException('DateRange requires fromInclusive to be earlier than toExclusive.');
        }

        $this->fromInclusive = $fromInclusive;
        $this->toExclusive = $toExclusive;
    }

    public function fromInclusive(): ?DateTimeImmutable
    {
        return $this->fromInclusive;
    }

    public function toExclusive(): ?DateTimeImmutable
    {
        return $this->toExclusive;
    }

    public function hasLowerBound(): bool
    {
        return $this->fromInclusive !== null;
    }

    public function hasUpperBound(): bool
    {
        return $this->toExclusive !== null;
    }

    public function equals(self $other): bool
    {
        return $this->fromInclusive == $other->fromInclusive
            && $this->toExclusive == $other->toExclusive;
    }
}
