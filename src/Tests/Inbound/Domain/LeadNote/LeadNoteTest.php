<?php

declare(strict_types=1);

namespace Tests\Inbound\Domain\LeadNote;

use DateTimeImmutable;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\LeadNote\LeadNote;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LeadNoteTest extends TestCase
{
    public function test_it_exposes_its_data_and_normalizes_note_text(): void
    {
        $leadId = new LeadId('lead-123');
        $createdAt = new DateTimeImmutable('2026-03-28T12:00:00+02:00');

        $leadNote = new LeadNote(
            $leadId,
            42,
            ' Need to call back tomorrow. ',
            $createdAt,
        );

        $this->assertSame($leadId, $leadNote->leadId());
        $this->assertSame(42, $leadNote->authorId());
        $this->assertSame('Need to call back tomorrow.', $leadNote->note());
        $this->assertSame($createdAt, $leadNote->createdAt());
    }

    public function test_it_allows_a_missing_author(): void
    {
        $leadNote = new LeadNote(
            new LeadId('lead-123'),
            null,
            'Sent follow-up details in messenger.',
            new DateTimeImmutable('2026-03-28T12:00:00+02:00'),
        );

        $this->assertNull($leadNote->authorId());
    }

    public function test_it_rejects_an_empty_note(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LeadNote(
            new LeadId('lead-123'),
            42,
            '',
            new DateTimeImmutable('2026-03-28T12:00:00+02:00'),
        );
    }

    public function test_it_rejects_a_whitespace_only_note(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LeadNote(
            new LeadId('lead-123'),
            42,
            '   ',
            new DateTimeImmutable('2026-03-28T12:00:00+02:00'),
        );
    }

    public function test_it_rejects_a_non_positive_author_id_when_provided(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new LeadNote(
            new LeadId('lead-123'),
            0,
            'Need more budget context.',
            new DateTimeImmutable('2026-03-28T12:00:00+02:00'),
        );
    }
}
