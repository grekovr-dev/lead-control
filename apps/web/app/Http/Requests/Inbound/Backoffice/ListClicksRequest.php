<?php

declare(strict_types=1);

namespace App\Http\Requests\Inbound\Backoffice;

use Illuminate\Foundation\Http\FormRequest;
use Inbound\Application\Queries\Backoffice\ListClicks\ListClicksQuery;

final class ListClicksRequest extends FormRequest
{
    private const DEFAULT_PER_PAGE = 20;

    /**
     * @var list<int>
     */
    private const PER_PAGE_OPTIONS = [20, 50, 100];

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function toQuery(): ListClicksQuery
    {
        return new ListClicksQuery(
            visitorId: $this->stringFilter('visitorId'),
            attributionSource: $this->stringFilter('attributionSource'),
            attributionMedium: $this->stringFilter('attributionMedium'),
            attributionCampaign: $this->stringFilter('attributionCampaign'),
            page: $this->pageFilter(),
            perPage: $this->perPageFilter(),
        );
    }

    /**
     * @return array{
     *     visitorId: ?string,
     *     attributionSource: ?string,
     *     attributionMedium: ?string,
     *     attributionCampaign: ?string,
     *     page: int,
     *     perPage: int
     * }
     */
    public function filters(): array
    {
        return [
            'visitorId' => $this->stringFilter('visitorId'),
            'attributionSource' => $this->stringFilter('attributionSource'),
            'attributionMedium' => $this->stringFilter('attributionMedium'),
            'attributionCampaign' => $this->stringFilter('attributionCampaign'),
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
     * @return array<string, int|string>
     */
    public function paginationQuery(): array
    {
        $filters = $this->filters();
        $query = [];

        if ($filters['visitorId'] !== null) {
            $query['visitorId'] = $filters['visitorId'];
        }

        if ($filters['attributionSource'] !== null) {
            $query['attributionSource'] = $filters['attributionSource'];
        }

        if ($filters['attributionMedium'] !== null) {
            $query['attributionMedium'] = $filters['attributionMedium'];
        }

        if ($filters['attributionCampaign'] !== null) {
            $query['attributionCampaign'] = $filters['attributionCampaign'];
        }

        if ($filters['perPage'] !== self::DEFAULT_PER_PAGE) {
            $query['perPage'] = $filters['perPage'];
        }

        return $query;
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
