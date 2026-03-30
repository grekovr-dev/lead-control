<?php

namespace App\Http\Requests\Inbound\Backoffice;

use Illuminate\Foundation\Http\FormRequest;
use Inbound\Application\Queries\Backoffice\ListLeads\ListLeadsQuery;
use Inbound\Domain\Lead\LeadStatus;

final class ListLeadsRequest extends FormRequest
{
    private const DEFAULT_PER_PAGE = 20;

    /**
     * @var list<int>
     */
    private const PER_PAGE_OPTIONS = [10, 20, 50];

    /**
     * @var array<string, string>
     */
    private const ORIGIN_OPTIONS = [
        'form' => 'Форма',
        'phone_click' => 'Клік по телефону',
        'messenger_click' => 'Клік по месенджеру',
    ];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toQuery(): ListLeadsQuery
    {
        return new ListLeadsQuery(
            status: $this->statusFilter(),
            origin: $this->originFilter(),
            attributionSource: $this->stringFilter('attributionSource'),
            attributionMedium: $this->stringFilter('attributionMedium'),
            page: $this->pageFilter(),
            perPage: $this->perPageFilter(),
        );
    }

    /**
     * @return array{
     *     status: ?string,
     *     origin: ?string,
     *     attributionSource: ?string,
     *     attributionMedium: ?string,
     *     page: int,
     *     perPage: int
     * }
     */
    public function filters(): array
    {
        return [
            'status' => $this->statusFilter()?->value,
            'origin' => $this->originFilter(),
            'attributionSource' => $this->stringFilter('attributionSource'),
            'attributionMedium' => $this->stringFilter('attributionMedium'),
            'page' => $this->pageFilter(),
            'perPage' => $this->perPageFilter(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function statusOptions(): array
    {
        return LeadStatus::options();
    }

    /**
     * @return array<string, string>
     */
    public function originOptions(): array
    {
        return self::ORIGIN_OPTIONS;
    }

    /**
     * @return list<int>
     */
    public function perPageOptions(): array
    {
        return self::PER_PAGE_OPTIONS;
    }

    /**
     * @return array<string, int|string>
     */
    public function paginationQuery(): array
    {
        $filters = $this->filters();
        $query = [];

        if ($filters['status'] !== null) {
            $query['status'] = $filters['status'];
        }

        if ($filters['origin'] !== null) {
            $query['origin'] = $filters['origin'];
        }

        if ($filters['attributionSource'] !== null) {
            $query['attributionSource'] = $filters['attributionSource'];
        }

        if ($filters['attributionMedium'] !== null) {
            $query['attributionMedium'] = $filters['attributionMedium'];
        }

        if ($filters['perPage'] !== self::DEFAULT_PER_PAGE) {
            $query['perPage'] = $filters['perPage'];
        }

        return $query;
    }

    private function statusFilter(): ?LeadStatus
    {
        $value = $this->stringFilter('status');

        return $value !== null ? LeadStatus::tryFrom($value) : null;
    }

    private function originFilter(): ?string
    {
        $value = $this->stringFilter('origin');

        if ($value === null) {
            return null;
        }

        return array_key_exists($value, self::ORIGIN_OPTIONS) ? $value : null;
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
