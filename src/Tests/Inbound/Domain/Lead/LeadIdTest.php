<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\Lead;

use Inbound\Domain\Lead\LeadId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LeadIdTest extends TestCase
{
    public function test_it_exposes_its_value(): void
    {
        $leadId = new LeadId('lead-123');

        $this->assertSame('lead-123', $leadId->value());
        $this->assertSame('lead-123', (string) $leadId);
    }

    public function test_it_normalizes_value_with_trim(): void
    {
        $leadId = new LeadId(' lead-123 ');

        $this->assertSame('lead-123', $leadId->value());
        $this->assertSame('lead-123', (string) $leadId);
    }

    public function test_it_compares_by_value(): void
    {
        $left = new LeadId('lead-123');
        $same = new LeadId(' lead-123 ');
        $different = new LeadId('lead-456');

        $this->assertTrue($left->equals($same));
        $this->assertFalse($left->equals($different));
    }

    public function test_it_rejects_an_empty_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LeadId('');
    }

    public function test_it_rejects_a_whitespace_only_string(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LeadId('   ');
    }
}
