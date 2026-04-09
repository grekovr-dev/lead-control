<?php

declare(strict_types=1);

namespace App\Http\Requests\Inbound\Backoffice;

use App\Http\Resolvers\Inbound\Backoffice\DateRangeQueryResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Inbound\Domain\Shared\DateRange;

final class LeadStatusReportRequest extends FormRequest
{
    /**
     * @var array<string, string>
     */
    private const PRESET_OPTIONS = [
        'all' => 'Усі дані',
        'last_7_days' => 'Останні 7 днів',
        'last_30_days' => 'Останні 30 днів',
        'current_month' => 'Поточний місяць',
        'previous_month' => 'Минулий місяць',
        'custom' => 'Період',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $preset = $this->normalizeString('preset');
        $from = $this->normalizeString('from');
        $to = $this->normalizeString('to');

        $this->merge([
            'preset' => $preset ?? 'all',
            'from' => $from,
            'to' => $to,
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'preset' => ['required', 'string', Rule::in(array_keys(self::PRESET_OPTIONS))],
            'from' => ['nullable', 'string'],
            'to' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'preset.in' => 'Невідомий пресет періоду.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'preset' => 'пресет періоду',
            'from' => 'від',
            'to' => 'до',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $from = $this->validatedDateString('from');
            $to = $this->validatedDateString('to');

            if ($from !== null && ! $this->isSupportedDateString($from)) {
                $validator->errors()->add('from', 'Поле від має бути коректною датою.');
            }

            if ($to !== null && ! $this->isSupportedDateString($to)) {
                $validator->errors()->add('to', 'Поле до має бути коректною датою.');
            }

            if ($this->validatedPreset() !== 'custom' || $validator->errors()->isNotEmpty()) {
                return;
            }

            if ($from === null || $to === null) {
                return;
            }

            $fromDate = $this->parseDateString($from);
            $toDate = $this->parseDateString($to);

            if ($fromDate !== null && $toDate !== null && $fromDate > $toDate) {
                $validator->errors()->add('range', 'Дата до не може бути раніше за дату від.');
            }
        });
    }

    public function resolveDateRange(DateRangeQueryResolver $resolver): ?DateRange
    {
        return $resolver->resolve($this);
    }

    /**
     * @return array<string, string>
     */
    public function presetOptions(): array
    {
        return self::PRESET_OPTIONS;
    }

    /**
     * @return array{
     *     preset: string,
     *     from: ?string,
     *     to: ?string
     * }
     */
    public function filters(): array
    {
        return [
            'preset' => $this->validatedPreset(),
            'from' => $this->normalizedDateInputValue('from'),
            'to' => $this->normalizedDateInputValue('to'),
        ];
    }

    private function validatedPreset(): string
    {
        $preset = $this->input('preset');

        return is_string($preset) && array_key_exists($preset, self::PRESET_OPTIONS)
            ? $preset
            : 'all';
    }

    private function validatedDateString(string $key): ?string
    {
        $value = $this->input($key);

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function normalizeString(string $key): ?string
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function isSupportedDateString(string $value): bool
    {
        return $this->parseDateString($value) !== null;
    }

    private function normalizedDateInputValue(string $key): ?string
    {
        $value = $this->validatedDateString($key);

        if ($value === null) {
            return null;
        }

        return $this->parseDateString($value)?->format('Y-m-d');
    }

    private function parseDateString(string $value): ?CarbonImmutable
    {
        $date = CarbonImmutable::createFromFormat('Y-m-d', $value, (string) config('app.timezone'));

        if ($date !== false && $date->format('Y-m-d') === $value) {
            return $date;
        }

        return null;
    }
}
