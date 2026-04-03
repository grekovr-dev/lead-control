<?php

namespace App\Http\Requests\Inbound\Capture;

final class CreateLeadFromFormRequest extends CaptureRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^\+[1-9]\d{1,14}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $phone = $this->input('phone');

        if (!is_string($phone)) {
            return;
        }

        $phone = preg_replace('/[^0-9+]+/', '', $phone);

        if ($phone === null) {
            return;
        }

        $phone = preg_replace('/(?!^)\+/', '', $phone);

        if ($phone === null) {
            return;
        }

        $this->merge([
            'phone' => $phone,
        ]);
    }
}
