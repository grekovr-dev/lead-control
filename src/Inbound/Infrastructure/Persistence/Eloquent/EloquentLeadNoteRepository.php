<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use DateTimeInterface;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\LeadNote\LeadNote;
use Inbound\Domain\LeadNote\LeadNoteRepository;
use UnexpectedValueException;

final class EloquentLeadNoteRepository implements LeadNoteRepository
{
    public function save(LeadNote $leadNote): void
    {
        $model = new LeadNoteModel();
        $model->fill([
            'lead_id' => $leadNote->leadId()->value(),
            'author_id' => $leadNote->authorId(),
            'note' => $leadNote->note(),
        ]);
        $model->setAttribute('created_at', $leadNote->createdAt());
        $model->setAttribute('updated_at', $leadNote->createdAt());
        $model->save();
    }

    public function findByLeadId(LeadId $leadId): array
    {
        /** @var list<LeadNoteModel> $models */
        $models = LeadNoteModel::query()
            ->where('lead_id', $leadId->value())
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->all();

        return array_map($this->mapModelToLeadNote(...), $models);
    }

    private function mapModelToLeadNote(LeadNoteModel $model): LeadNote
    {
        return new LeadNote(
            new LeadId((string) $model->getAttribute('lead_id')),
            $this->nullablePositiveInt($model->getAttribute('author_id')),
            (string) $model->getAttribute('note'),
            $this->toDateTimeImmutable($model->getAttribute('created_at')),
        );
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_numeric($value)) {
            $value = (int) $value;

            return $value > 0 ? $value : null;
        }

        return null;
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if (!$value instanceof DateTimeInterface) {
            throw new UnexpectedValueException('Expected a date/time value from LeadNoteModel.');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
