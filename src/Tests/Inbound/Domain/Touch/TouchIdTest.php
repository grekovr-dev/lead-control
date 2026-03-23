<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\Touch;

use Inbound\Domain\Touch\TouchId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class TouchIdTest extends TestCase
{
    public function test_it_exposes_its_value(): void
    {
        $touchId = new TouchId('touch-123');

        $this->assertSame('touch-123', $touchId->value());
        $this->assertSame('touch-123', (string) $touchId);
    }

    public function test_it_normalizes_value_with_trim(): void
    {
        $touchId = new TouchId(' touch-123 ');

        $this->assertSame('touch-123', $touchId->value());
        $this->assertSame('touch-123', (string) $touchId);
    }

    public function test_it_compares_by_value(): void
    {
        $left = new TouchId('touch-123');
        $same = new TouchId(' touch-123 ');
        $different = new TouchId('touch-456');

        $this->assertTrue($left->equals($same));
        $this->assertFalse($left->equals($different));
    }

    public function test_it_rejects_an_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TouchId('');
    }

    public function test_it_rejects_a_whitespace_only_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new TouchId('   ');
    }
}
