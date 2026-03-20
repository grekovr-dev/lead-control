<?php

declare(strict_types=1);

namespace Tests\Unit\Inbound\Domain\Click;

use Inbound\Domain\Click\ClickId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ClickIdTest extends TestCase
{
    public function test_it_exposes_its_value(): void
    {
        $clickId = new ClickId('click-123');

        $this->assertSame('click-123', $clickId->value());
        $this->assertSame('click-123', (string) $clickId);
    }

    public function test_it_normalizes_value_with_trim(): void
    {
        $clickId = new ClickId(' click-123 ');

        $this->assertSame('click-123', $clickId->value());
        $this->assertSame('click-123', (string) $clickId);
    }

    public function test_it_compares_by_value(): void
    {
        $left = new ClickId('click-123');
        $same = new ClickId(' click-123 ');
        $different = new ClickId('click-456');

        $this->assertTrue($left->equals($same));
        $this->assertFalse($left->equals($different));
    }

    public function test_it_rejects_an_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ClickId('');
    }

    public function test_it_rejects_a_whitespace_only_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ClickId('   ');
    }
}
