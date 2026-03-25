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
            'phone' => ['required', 'string', 'max:255'],
        ];
    }
}
