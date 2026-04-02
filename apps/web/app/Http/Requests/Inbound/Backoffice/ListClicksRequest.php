<?php

declare(strict_types=1);

namespace App\Http\Requests\Inbound\Backoffice;

use App\Http\Resolvers\Inbound\Backoffice\DateRangeQueryResolver;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Inbound\Application\Queries\Backoffice\ListClicks\ListClicksQuery;
use Inbound\Domain\Shared\DateRange;

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

    public function toQuery(DateRangeQueryResolver $dateRangeResolver): ListClicksQuery
    {
        $filters = $this->filters($dateRangeResolver);

        return new ListClicksQuery(
            visitorId: $filters['visitorId'],
            attributionSource: $filters['attributionSource'],
            attributionSourceMissing: $filters['attributionSourceMissing'],
            attributionMedium: $filters['attributionMedium'],
            attributionMediumMissing: $filters['attributionMediumMissing'],
            attributionCampaign: $filters['attributionCampaign'],
            attributionCampaignMissing: $filters['attributionCampaignMissing'],
            occurredAtRange: $dateRangeResolver->resolve($this),
            page: $filters['page'],
            perPage: $filters['perPage'],
        );
    }

    /**
     * @return array{
     *     visitorId: ?string,
     *     attributionSource: ?string,
     *     attributionSourceMissing: bool,
     *     attributionMedium: ?string,
     *     attributionMediumMissing: bool,
     *     attributionCampaign: ?string,
     *     attributionCampaignMissing: bool,
     *     preset: string,
     *     from: ?string,
     *     to: ?string,
     *     page: int,
     *     perPage: int
     * }
     */
    public function filters(DateRangeQueryResolver $dateRangeResolver): array
    {
        unset($dateRangeResolver);

        $preset = $this->presetFilter();
        $from = $this->dateStringFilter('from');
        $to = $this->dateStringFilter('to');
        $attributionSourceMissing = $this->booleanFilter('attributionSourceMissing');
        $attributionMediumMissing = $this->booleanFilter('attributionMediumMissing');
        $attributionCampaignMissing = $this->booleanFilter('attributionCampaignMissing');

        return [
            'visitorId' => $this->stringFilter('visitorId'),
            'attributionSource' => $attributionSourceMissing ? null : $this->stringFilter('attributionSource'),
            'attributionSourceMissing' => $attributionSourceMissing,
            'attributionMedium' => $attributionMediumMissing ? null : $this->stringFilter('attributionMedium'),
            'attributionMediumMissing' => $attributionMediumMissing,
            'attributionCampaign' => $attributionCampaignMissing ? null : $this->stringFilter('attributionCampaign'),
            'attributionCampaignMissing' => $attributionCampaignMissing,
            'preset' => $preset,
            'from' => $from,
            'to' => $to,
            'page' => $this->pageFilter(),
            'perPage' => $this->perPageFilter(),
        ];
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    public function drillContextItems(DateRangeQueryResolver $dateRangeResolver): array
    {
        $filters = $this->filters($dateRangeResolver);
        $occurredAtRange = $dateRangeResolver->resolve($this);
        $items = [];

        if ($filters['visitorId'] !== null) {
            $items[] = ['label' => 'ID відвідувача', 'value' => $filters['visitorId']];
        }

        if ($filters['attributionSourceMissing']) {
            $items[] = ['label' => 'Джерело атрибуції', 'value' => 'Без атрибуції'];
        } elseif ($filters['attributionSource'] !== null) {
            $items[] = ['label' => 'Джерело атрибуції', 'value' => $filters['attributionSource']];
        }

        if ($filters['attributionMediumMissing']) {
            $items[] = ['label' => 'Канал атрибуції', 'value' => 'Без атрибуції'];
        } elseif ($filters['attributionMedium'] !== null) {
            $items[] = ['label' => 'Канал атрибуції', 'value' => $filters['attributionMedium']];
        }

        if ($filters['attributionCampaignMissing']) {
            $items[] = ['label' => 'Кампанія', 'value' => 'Без кампанії'];
        } elseif ($filters['attributionCampaign'] !== null) {
            $items[] = ['label' => 'Кампанія', 'value' => $filters['attributionCampaign']];
        }

        if ($occurredAtRange !== null) {
            $items[] = ['label' => 'Період кліків', 'value' => $this->formatDateRange($occurredAtRange)];
        }

        return $items;
    }

    /**
     * @return array<string, int|string>
     */
    public function paginationQuery(DateRangeQueryResolver $dateRangeResolver): array
    {
        $filters = $this->filters($dateRangeResolver);
        $query = [];

        if ($filters['visitorId'] !== null) {
            $query['visitorId'] = $filters['visitorId'];
        }

        if ($filters['attributionSourceMissing']) {
            $query['attributionSourceMissing'] = '1';
        } elseif ($filters['attributionSource'] !== null) {
            $query['attributionSource'] = $filters['attributionSource'];
        }

        if ($filters['attributionMediumMissing']) {
            $query['attributionMediumMissing'] = '1';
        } elseif ($filters['attributionMedium'] !== null) {
            $query['attributionMedium'] = $filters['attributionMedium'];
        }

        if ($filters['attributionCampaignMissing']) {
            $query['attributionCampaignMissing'] = '1';
        } elseif ($filters['attributionCampaign'] !== null) {
            $query['attributionCampaign'] = $filters['attributionCampaign'];
        }

        if ($filters['preset'] !== 'all') {
            $query['preset'] = $filters['preset'];
        }

        if ($filters['from'] !== null) {
            $query['from'] = $filters['from'];
        }

        if ($filters['to'] !== null) {
            $query['to'] = $filters['to'];
        }

        if ($filters['perPage'] !== self::DEFAULT_PER_PAGE) {
            $query['perPage'] = $filters['perPage'];
        }

        return $query;
    }

    private function pageFilter(): int
    {
        $value = filter_var($this->input('page'), FILTER_VALIDATE_INT);

        if (! is_int($value) || $value < 1) {
            return 1;
        }

        return $value;
    }

    private function perPageFilter(): int
    {
        $value = filter_var($this->input('perPage'), FILTER_VALIDATE_INT);

        if (! is_int($value) || ! in_array($value, self::PER_PAGE_OPTIONS, true)) {
            return self::DEFAULT_PER_PAGE;
        }

        return $value;
    }

    private function stringFilter(string $key): ?string
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }

    private function presetFilter(): string
    {
        $preset = $this->stringFilter('preset');

        return match ($preset) {
            'last_7_days', 'last_30_days', 'current_month', 'previous_month', 'custom' => $preset,
            default => 'all',
        };
    }

    private function dateStringFilter(string $key): ?string
    {
        $value = $this->stringFilter($key);

        if ($value === null) {
            return null;
        }

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }

    private function booleanFilter(string $key): bool
    {
        return filter_var($this->input($key), FILTER_VALIDATE_BOOLEAN) === true;
    }

    private function formatDateRange(DateRange $range): string
    {
        $from = $range->fromInclusive();
        $to = $range->toExclusive()?->modify('-1 day');

        if ($from !== null && $to !== null) {
            return sprintf('%s - %s', $this->formatDate($from), $this->formatDate($to));
        }

        if ($from !== null) {
            return 'від '.$this->formatDate($from);
        }

        if ($to !== null) {
            return 'до '.$this->formatDate($to);
        }

        return 'Усі дані';
    }

    private function formatDate(DateTimeImmutable $date): string
    {
        return $date->format('d.m.Y');
    }
}
