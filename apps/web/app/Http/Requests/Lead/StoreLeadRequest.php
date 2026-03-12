<?php

namespace App\Http\Requests\Lead;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:32'],
            'name' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        /** @var array<string, mixed> $payload */
        $payload = $this->all();

        $name = $payload['name'] ?? null;
        $phone = $payload['phone'] ?? null;
        $comment = $payload['comment'] ?? null;

        $name = is_string($name) ? trim($name) : $name;
        $phone = is_string($phone) ? trim($phone) : $phone;
        $comment = is_string($comment) ? trim($comment) : $comment;

        $this->merge([
            'phone' => $phone,
            'name' => $name === '' ? null : $name,
            'comment' => $comment === '' ? null : $comment,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.required' => 'Please enter your phone number.',
            'phone.max' => 'The phone number may not be greater than 32 characters.',
            'name.max' => 'The name may not be greater than 255 characters.',
            'comment.max' => 'The comment may not be greater than 2000 characters.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        $url = parent::getRedirectUrl();

        return str_contains($url, '#') ? $url : $url.'#lead-form';
    }
}
