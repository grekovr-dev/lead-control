<?php

declare(strict_types=1);

namespace App\Http\Requests\Inbound\Backoffice;

use App\Http\Resolvers\Inbound\Backoffice\DateRangeQueryResolver;
use DateTimeImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Inbound\Application\Queries\Backoffice\ListVisits\ListVisitsQuery;
use Inbound\Domain\Shared\DateRange;

final class ListVisitsRequest extends FormRequest
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

    public function toQuery(DateRangeQueryResolver $dateRangeResolver): ListVisitsQuery
    {
        $filters = $this->filters($dateRangeResolver);

        return new ListVisitsQuery(
            visitorId: $filters['visitorId'],
            firstAttributionSource: $filters['firstAttributionSource'],
            firstAttributionSourceMissing: $filters['firstAttributionSourceMissing'],
            firstAttributionMedium: $filters['firstAttributionMedium'],
            firstAttributionMediumMissing: $filters['firstAttributionMediumMissing'],
            firstAttributionCampaign: $filters['firstAttributionCampaign'],
            firstAttributionCampaignMissing: $filters['firstAttributionCampaignMissing'],
            lastAttributionSource: $filters['lastAttributionSource'],
            lastAttributionMedium: $filters['lastAttributionMedium'],
            startedAtRange: $dateRangeResolver->resolve($this),
            page: $filters['page'],
            perPage: $filters['perPage'],
        );
    }

    /**
     * @return array{
     *     visitorId: ?string,
     *     firstAttributionSource: ?string,
     *     firstAttributionSourceMissing: bool,
     *     firstAttributionMedium: ?string,
     *     firstAttributionMediumMissing: bool,
     *     firstAttributionCampaign: ?string,
     *     firstAttributionCampaignMissing: bool,
     *     lastAttributionSource: ?string,
     *     lastAttributionMedium: ?string,
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
        $firstAttributionSourceMissing = $this->booleanFilter('firstAttributionSourceMissing');
        $firstAttributionMediumMissing = $this->booleanFilter('firstAttributionMediumMissing');
        $firstAttributionCampaignMissing = $this->booleanFilter('firstAttributionCampaignMissing');

        return [
            'visitorId' => $this->stringFilter('visitorId'),
            'firstAttributionSource' => $firstAttributionSourceMissing ? null : $this->stringFilter('firstAttributionSource'),
            'firstAttributionSourceMissing' => $firstAttributionSourceMissing,
            'firstAttributionMedium' => $firstAttributionMediumMissing ? null : $this->stringFilter('firstAttributionMedium'),
            'firstAttributionMediumMissing' => $firstAttributionMediumMissing,
            'firstAttributionCampaign' => $firstAttributionCampaignMissing ? null : $this->stringFilter('firstAttributionCampaign'),
            'firstAttributionCampaignMissing' => $firstAttributionCampaignMissing,
            'lastAttributionSource' => $this->stringFilter('lastAttributionSource'),
            'lastAttributionMedium' => $this->stringFilter('lastAttributionMedium'),
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
        $startedAtRange = $dateRangeResolver->resolve($this);
        $items = [];

        if ($filters['visitorId'] !== null) {
            $items[] = ['label' => 'ID відвідувача', 'value' => $filters['visitorId']];
        }

        if ($filters['firstAttributionSourceMissing']) {
            $items[] = ['label' => 'Перше джерело', 'value' => 'Без атрибуції'];
        } elseif ($filters['firstAttributionSource'] !== null) {
            $items[] = ['label' => 'Перше джерело', 'value' => $filters['firstAttributionSource']];
        }

        if ($filters['firstAttributionMediumMissing']) {
            $items[] = ['label' => 'Перший канал', 'value' => 'Без атрибуції'];
        } elseif ($filters['firstAttributionMedium'] !== null) {
            $items[] = ['label' => 'Перший канал', 'value' => $filters['firstAttributionMedium']];
        }

        if ($filters['firstAttributionCampaignMissing']) {
            $items[] = ['label' => 'Перша кампанія', 'value' => 'Без кампанії'];
        } elseif ($filters['firstAttributionCampaign'] !== null) {
            $items[] = ['label' => 'Перша кампанія', 'value' => $filters['firstAttributionCampaign']];
        }

        if ($filters['lastAttributionSource'] !== null) {
            $items[] = ['label' => 'Останнє джерело', 'value' => $filters['lastAttributionSource']];
        }

        if ($filters['lastAttributionMedium'] !== null) {
            $items[] = ['label' => 'Останній канал', 'value' => $filters['lastAttributionMedium']];
        }

        if ($startedAtRange !== null) {
            $items[] = ['label' => 'Період візитів', 'value' => $this->formatDateRange($startedAtRange)];
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

        if ($filters['firstAttributionSourceMissing']) {
            $query['firstAttributionSourceMissing'] = '1';
        } elseif ($filters['firstAttributionSource'] !== null) {
            $query['firstAttributionSource'] = $filters['firstAttributionSource'];
        }

        if ($filters['firstAttributionMediumMissing']) {
            $query['firstAttributionMediumMissing'] = '1';
        } elseif ($filters['firstAttributionMedium'] !== null) {
            $query['firstAttributionMedium'] = $filters['firstAttributionMedium'];
        }

        if ($filters['firstAttributionCampaignMissing']) {
            $query['firstAttributionCampaignMissing'] = '1';
        } elseif ($filters['firstAttributionCampaign'] !== null) {
            $query['firstAttributionCampaign'] = $filters['firstAttributionCampaign'];
        }

        if ($filters['lastAttributionSource'] !== null) {
            $query['lastAttributionSource'] = $filters['lastAttributionSource'];
        }

        if ($filters['lastAttributionMedium'] !== null) {
            $query['lastAttributionMedium'] = $filters['lastAttributionMedium'];
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
