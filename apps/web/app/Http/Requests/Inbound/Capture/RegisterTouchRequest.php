<?php

namespace App\Http\Requests\Inbound\Capture;

use Illuminate\Validation\Rule;
use Inbound\Domain\Touch\TouchType;

final class RegisterTouchRequest extends CaptureRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', Rule::enum(TouchType::class)],
        ];
    }
}
