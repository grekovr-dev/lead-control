<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\Revisit;

use Inbound\Domain\Revisit\RevisitId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class RevisitIdTest extends TestCase
{
    public function test_it_exposes_its_value(): void
    {
        $revisitId = new RevisitId('revisit-123');

        $this->assertSame('revisit-123', $revisitId->value());
        $this->assertSame('revisit-123', (string) $revisitId);
    }

    public function test_it_normalizes_value_with_trim(): void
    {
        $revisitId = new RevisitId(' revisit-123 ');

        $this->assertSame('revisit-123', $revisitId->value());
        $this->assertSame('revisit-123', (string) $revisitId);
    }

    public function test_it_compares_by_value(): void
    {
        $left = new RevisitId('revisit-123');
        $same = new RevisitId(' revisit-123 ');
        $different = new RevisitId('revisit-456');

        $this->assertTrue($left->equals($same));
        $this->assertFalse($left->equals($different));
    }

    public function test_it_rejects_an_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RevisitId('');
    }

    public function test_it_rejects_a_whitespace_only_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new RevisitId('   ');
    }
}
