<?php

declare(strict_types=1);

namespace Inbound\Application\Actions;

use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadNoteModel;

class AddLeadNoteAction
{
    public function execute(
        LeadModel $lead,
        string $note,
        ?int $authorId = null,
    ): LeadNoteModel {
        return $lead->notes()->create([
            'author_id' => $authorId,
            'note' => $note,
        ]);
    }
}
