<?php

declare(strict_types=1);

namespace App\Http\Requests\Inbound\Backoffice;

use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Inbound\Application\Actions\Backoffice\AddLeadNote\AddLeadNoteCommand;
use Inbound\Domain\Lead\LeadId;

final class StoreLeadNoteRequest extends FormRequest
{
    private const NOTE_FORM_FRAGMENT = 'lead-note-form';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $note = $this->input('note');

        if (is_string($note)) {
            $this->merge([
                'note' => trim($note),
            ]);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'note' => ['bail', 'required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'note.required' => 'Поле нотатка є обов’язковим.',
            'note.string' => 'Поле нотатка має бути текстом.',
            'note.max' => 'Нотатка не може бути довшою за 5000 символів.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'note' => 'нотатка',
        ];
    }

    protected function getRedirectUrl(): string
    {
        $leadId = $this->route('leadId');

        if (!is_string($leadId) || $leadId === '') {
            return parent::getRedirectUrl();
        }

        return route('admin.leads.show', ['leadId' => $leadId]).'#'.self::NOTE_FORM_FRAGMENT;
    }

    public function toCommand(string $leadId, ?int $authorId): AddLeadNoteCommand
    {
        return new AddLeadNoteCommand(
            leadId: new LeadId($leadId),
            authorId: $authorId,
            note: (string) $this->validated('note'),
            createdAt: DateTimeImmutable::createFromInterface(now()),
        );
    }
}
