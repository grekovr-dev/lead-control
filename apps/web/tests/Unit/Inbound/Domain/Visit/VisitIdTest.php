<?php

declare(strict_types=1);

namespace Tests\Unit\Inbound\Domain\Visit;

use Inbound\Domain\Visit\VisitId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class VisitIdTest extends TestCase
{
    public function test_it_exposes_its_value(): void
    {
        $visitId = new VisitId('visit-123');

        $this->assertSame('visit-123', $visitId->value());
        $this->assertSame('visit-123', (string) $visitId);
    }

    public function test_it_normalizes_value_with_trim(): void
    {
        $visitId = new VisitId(' visit-123 ');

        $this->assertSame('visit-123', $visitId->value());
        $this->assertSame('visit-123', (string) $visitId);
    }

    public function test_it_compares_by_value(): void
    {
        $left = new VisitId('visit-123');
        $same = new VisitId(' visit-123 ');
        $different = new VisitId('visit-456');

        $this->assertTrue($left->equals($same));
        $this->assertFalse($left->equals($different));
    }

    public function test_it_rejects_an_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new VisitId('');
    }

    public function test_it_rejects_a_whitespace_only_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new VisitId('   ');
    }
}
