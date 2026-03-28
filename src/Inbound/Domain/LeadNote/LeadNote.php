<?php

declare(strict_types=1);

namespace Inbound\Domain\LeadNote;

use DateTimeImmutable;
use Inbound\Domain\Lead\LeadId;
use InvalidArgumentException;

final class LeadNote
{
    private LeadId $leadId;
    private ?int $authorId;
    private string $note;
    private DateTimeImmutable $createdAt;

    public function __construct(
        LeadId $leadId,
        ?int $authorId,
        string $note,
        DateTimeImmutable $createdAt,
    ) {
        $note = trim($note);

        if ($note === '') {
            throw new InvalidArgumentException('LeadNote note cannot be empty.');
        }

        if ($authorId !== null && $authorId <= 0) {
            throw new InvalidArgumentException('LeadNote authorId must be positive when provided.');
        }

        $this->leadId = $leadId;
        $this->authorId = $authorId;
        $this->note = $note;
        $this->createdAt = $createdAt;
    }

    public function leadId(): LeadId
    {
        return $this->leadId;
    }

    public function authorId(): ?int
    {
        return $this->authorId;
    }

    public function note(): string
    {
        return $this->note;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }
}
