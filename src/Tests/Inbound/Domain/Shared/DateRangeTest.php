<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\Shared;

use DateTimeImmutable;
use Inbound\Domain\Shared\DateRange;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class DateRangeTest extends TestCase
{
    public function test_it_accepts_a_bounded_range_and_exposes_its_boundaries(): void
    {
        $from = new DateTimeImmutable('2026-03-01 00:00:00');
        $to = new DateTimeImmutable('2026-04-01 00:00:00');

        $range = new DateRange($from, $to);

        $this->assertSame($from, $range->fromInclusive());
        $this->assertSame($to, $range->toExclusive());
        $this->assertTrue($range->hasLowerBound());
        $this->assertTrue($range->hasUpperBound());
    }

    public function test_it_accepts_a_range_with_only_a_lower_bound(): void
    {
        $from = new DateTimeImmutable('2026-03-01 00:00:00');

        $range = new DateRange($from, null);

        $this->assertSame($from, $range->fromInclusive());
        $this->assertNull($range->toExclusive());
        $this->assertTrue($range->hasLowerBound());
        $this->assertFalse($range->hasUpperBound());
    }

    public function test_it_accepts_a_range_with_only_an_upper_bound(): void
    {
        $to = new DateTimeImmutable('2026-04-01 00:00:00');

        $range = new DateRange(null, $to);

        $this->assertNull($range->fromInclusive());
        $this->assertSame($to, $range->toExclusive());
        $this->assertFalse($range->hasLowerBound());
        $this->assertTrue($range->hasUpperBound());
    }

    public function test_it_compares_ranges_by_value(): void
    {
        $left = new DateRange(
            new DateTimeImmutable('2026-03-01 00:00:00'),
            new DateTimeImmutable('2026-04-01 00:00:00'),
        );
        $same = new DateRange(
            new DateTimeImmutable('2026-03-01 00:00:00'),
            new DateTimeImmutable('2026-04-01 00:00:00'),
        );
        $different = new DateRange(
            new DateTimeImmutable('2026-03-02 00:00:00'),
            new DateTimeImmutable('2026-04-01 00:00:00'),
        );

        $this->assertTrue($left->equals($same));
        $this->assertFalse($left->equals($different));
    }

    public function test_it_rejects_a_range_without_any_boundaries(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DateRange(null, null);
    }

    public function test_it_rejects_a_range_with_non_increasing_boundaries(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DateRange(
            new DateTimeImmutable('2026-04-01 00:00:00'),
            new DateTimeImmutable('2026-04-01 00:00:00'),
        );
    }
}
