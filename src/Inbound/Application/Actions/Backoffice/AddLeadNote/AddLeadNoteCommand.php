<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Backoffice\AddLeadNote;

use DateTimeImmutable;
use Inbound\Domain\Lead\LeadId;

final readonly class AddLeadNoteCommand
{
    public function __construct(
        public LeadId $leadId,
        public ?int $authorId,
        public string $note,
        public DateTimeImmutable $createdAt,
    ) {
    }
}
