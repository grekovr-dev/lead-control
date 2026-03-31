<?php

declare(strict_types=1);

namespace App\Http\Requests\Inbound\Backoffice;

use Illuminate\Foundation\Http\FormRequest;
use Inbound\Application\Queries\Backoffice\ListTouches\ListTouchesQuery;
use Inbound\Domain\Touch\TouchType;

final class ListTouchesRequest extends FormRequest
{
    private const DEFAULT_PER_PAGE = 20;

    /**
     * @var list<int>
     */
    private const PER_PAGE_OPTIONS = [20, 50, 100];

    /**
     * @var array<string, string>
     */
    private const TYPE_OPTIONS = [
        'phone_click' => 'Клік по телефону',
        'lead_form_click' => 'Клік по формі',
        'messenger_click' => 'Клік по месенджеру',
        'works_click' => 'Клік по роботах',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toQuery(): ListTouchesQuery
    {
        return new ListTouchesQuery(
            visitId: $this->stringFilter('visitId'),
            visitorId: $this->stringFilter('visitorId'),
            type: $this->typeFilter(),
            page: $this->pageFilter(),
            perPage: $this->perPageFilter(),
        );
    }

    /**
     * @return array{
     *     visitId: ?string,
     *     visitorId: ?string,
     *     type: ?string,
     *     page: int,
     *     perPage: int
     * }
     */
    public function filters(): array
    {
        return [
            'visitId' => $this->stringFilter('visitId'),
            'visitorId' => $this->stringFilter('visitorId'),
            'type' => $this->typeFilter()?->value,
            'page' => $this->pageFilter(),
            'perPage' => $this->perPageFilter(),
        ];
    }

    /**
     * @return list<int>
     */
    public function perPageOptions(): array
    {
        return self::PER_PAGE_OPTIONS;
    }

    /**
     * @return array<string, string>
     */
    public function typeOptions(): array
    {
        return self::TYPE_OPTIONS;
    }

    /**
     * @return array<string, int|string>
     */
    public function paginationQuery(): array
    {
        $filters = $this->filters();
        $query = [];

        if ($filters['visitId'] !== null) {
            $query['visitId'] = $filters['visitId'];
        }

        if ($filters['visitorId'] !== null) {
            $query['visitorId'] = $filters['visitorId'];
        }

        if ($filters['type'] !== null) {
            $query['type'] = $filters['type'];
        }

        if ($filters['perPage'] !== self::DEFAULT_PER_PAGE) {
            $query['perPage'] = $filters['perPage'];
        }

        return $query;
    }

    private function typeFilter(): ?TouchType
    {
        $value = $this->stringFilter('type');

        if ($value === null) {
            return null;
        }

        return array_key_exists($value, self::TYPE_OPTIONS) ? TouchType::from($value) : null;
    }

    private function pageFilter(): int
    {
        $value = filter_var($this->input('page'), FILTER_VALIDATE_INT);

        if (!is_int($value) || $value < 1) {
            return 1;
        }

        return $value;
    }

    private function perPageFilter(): int
    {
        $value = filter_var($this->input('perPage'), FILTER_VALIDATE_INT);

        if (!is_int($value) || !in_array($value, self::PER_PAGE_OPTIONS, true)) {
            return self::DEFAULT_PER_PAGE;
        }

        return $value;
    }

    private function stringFilter(string $key): ?string
    {
        $value = $this->input($key);

        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
