<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Inbound\Application\Queries\Backoffice\ListClicks\ClickListItemView;
use Inbound\Application\Queries\Backoffice\ListClicks\ClicksListReadModel;
use Inbound\Application\Queries\Backoffice\ListClicks\ClicksListView;
use Inbound\Application\Queries\Backoffice\ListClicks\ListClicksQuery;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use UnexpectedValueException;

final class EloquentClicksListReadModel implements ClicksListReadModel
{
    public function __invoke(ListClicksQuery $query): ClicksListView
    {
        $page = max(1, $query->page);
        $perPage = max(1, $query->perPage);

        $clickQuery = ClickModel::query();
        $this->applyFilters($clickQuery, $query);

        $total = (clone $clickQuery)->count();

        /** @var list<ClickModel> $models */
        $models = $clickQuery
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get()
            ->all();

        $items = [];

        foreach ($models as $model) {
            $items[] = new ClickListItemView(
                clickId: (string) $model->getAttribute('id'),
                visitorId: (string) $model->getAttribute('visitor_id'),
                landingUrl: (string) $model->getAttribute('landing_url'),
                referrer: $this->nullableString($model->getAttribute('attribution_referrer')),
                attributionSource: $this->nullableString($model->getAttribute('attribution_source')),
                attributionMedium: $this->nullableString($model->getAttribute('attribution_medium')),
                attributionCampaign: $this->nullableString($model->getAttribute('attribution_campaign')),
                occurredAt: $this->toDateTimeImmutable($model->getAttribute('occurred_at')),
            );
        }

        return new ClicksListView(
            currentPage: $page,
            perPage: $perPage,
            total: $total,
            lastPage: max(1, (int) ceil($total / $perPage)),
            items: $items,
        );
    }

    private function applyFilters(Builder $clickQuery, ListClicksQuery $query): void
    {
        if ($query->visitorId !== null) {
            $clickQuery->where('visitor_id', $query->visitorId);
        }

        if ($query->attributionSource !== null) {
            $clickQuery->where('attribution_source', $query->attributionSource);
        }

        if ($query->attributionMedium !== null) {
            $clickQuery->where('attribution_medium', $query->attributionMedium);
        }

        if ($query->attributionCampaign !== null) {
            $clickQuery->where('attribution_campaign', $query->attributionCampaign);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if (!$value instanceof DateTimeInterface) {
            throw new UnexpectedValueException('Expected a date/time value from ClickModel.');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
