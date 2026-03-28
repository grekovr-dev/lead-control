<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Backoffice\AddLeadNote;

use Inbound\Domain\Lead\LeadRepository;
use Inbound\Domain\LeadNote\LeadNote;
use Inbound\Domain\LeadNote\LeadNoteRepository;

final class AddLeadNoteAction
{
    public function __construct(
        private LeadRepository $leadRepository,
        private LeadNoteRepository $leadNoteRepository,
    ) {
    }

    /**
     * @throws LeadNotFoundException
     */
    public function __invoke(AddLeadNoteCommand $command): LeadNote
    {
        $lead = $this->leadRepository->findById($command->leadId);

        if ($lead === null) {
            throw new LeadNotFoundException(sprintf(
                'Cannot add note to missing lead "%s".',
                $command->leadId->value(),
            ));
        }

        $leadNote = new LeadNote(
            $lead->id(),
            $command->authorId,
            $command->note,
            $command->createdAt,
        );

        $this->leadNoteRepository->save($leadNote);

        return $leadNote;
    }
}
