<?php

namespace App\Http\Requests\Inbound\Capture;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class CaptureRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'ok' => false,
            'code' => 'validation_error',
            'message' => __('validation.failed'),
            'errors' => $validator->errors()->toArray(),
        ], 422));
    }
}
