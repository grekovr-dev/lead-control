<?php

declare(strict_types=1);

namespace App\Http\Requests\Inbound\Backoffice;

use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Inbound\Application\Actions\Backoffice\ChangeLeadStatus\ChangeLeadStatusCommand;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Lead\LeadStatus;

final class UpdateLeadStatusRequest extends FormRequest
{
    private const STATUS_FORM_FRAGMENT = 'lead-status-form';
    private const RULE_KEY = 'backoffice.manual_change';

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'status' => ['bail', 'required', 'string', 'in:'.implode(',', array_keys(LeadStatus::options()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Поле статус є обов’язковим.',
            'status.string' => 'Поле статус має бути рядком.',
            'status.in' => 'Оберіть коректний статус ліда.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'status' => 'статус',
        ];
    }

    protected function getRedirectUrl(): string
    {
        $leadId = $this->route('leadId');

        if (!is_string($leadId) || $leadId === '') {
            return parent::getRedirectUrl();
        }

        return route('admin.leads.show', ['leadId' => $leadId]).'#'.self::STATUS_FORM_FRAGMENT;
    }

    public function toCommand(string $leadId): ChangeLeadStatusCommand
    {
        return new ChangeLeadStatusCommand(
            leadId: new LeadId($leadId),
            status: LeadStatus::from((string) $this->validated('status')),
            ruleKey: self::RULE_KEY,
            changedAt: DateTimeImmutable::createFromInterface(now()),
        );
    }
}
