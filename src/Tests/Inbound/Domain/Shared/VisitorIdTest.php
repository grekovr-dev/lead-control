<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\Shared;

use Inbound\Domain\Shared\VisitorId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class VisitorIdTest extends TestCase
{
    public function test_it_exposes_its_value(): void
    {
        $visitorId = new VisitorId('visitor-123');

        $this->assertSame('visitor-123', $visitorId->value());
        $this->assertSame('visitor-123', (string) $visitorId);
    }

    public function test_it_normalizes_value_with_trim(): void
    {
        $visitorId = new VisitorId(' visitor-123 ');

        $this->assertSame('visitor-123', $visitorId->value());
        $this->assertSame('visitor-123', (string) $visitorId);
    }

    public function test_it_compares_by_value(): void
    {
        $left = new VisitorId('visitor-123');
        $same = new VisitorId(' visitor-123 ');
        $different = new VisitorId('visitor-456');

        $this->assertTrue($left->equals($same));
        $this->assertFalse($left->equals($different));
    }

    public function test_it_rejects_an_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new VisitorId('');
    }

    public function test_it_rejects_a_whitespace_only_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new VisitorId('   ');
    }
}
