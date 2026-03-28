<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Inbound\Application\Queries\Backoffice\ListVisits\ListVisitsQuery;
use Inbound\Application\Queries\Backoffice\ListVisits\VisitListItemView;
use Inbound\Application\Queries\Backoffice\ListVisits\VisitsListReadModel;
use Inbound\Application\Queries\Backoffice\ListVisits\VisitsListView;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use UnexpectedValueException;

final class EloquentVisitsListReadModel implements VisitsListReadModel
{
    public function __invoke(ListVisitsQuery $query): VisitsListView
    {
        $page = max(1, $query->page);
        $perPage = max(1, $query->perPage);

        $visitQuery = VisitModel::query();
        $this->applyFilters($visitQuery, $query);

        $total = (clone $visitQuery)->count();

        /** @var list<VisitModel> $models */
        $models = $visitQuery
            ->orderByDesc('last_touched_at')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get()
            ->all();

        $items = [];

        foreach ($models as $model) {
            $items[] = new VisitListItemView(
                visitId: (string) $model->getAttribute('id'),
                visitorId: (string) $model->getAttribute('visitor_id'),
                firstAttributionSource: $this->nullableString($model->getAttribute('first_attribution_source')),
                firstAttributionMedium: $this->nullableString($model->getAttribute('first_attribution_medium')),
                lastAttributionSource: $this->nullableString($model->getAttribute('last_attribution_source')),
                lastAttributionMedium: $this->nullableString($model->getAttribute('last_attribution_medium')),
                startedAt: $this->toDateTimeImmutable($model->getAttribute('started_at')),
                lastTouchedAt: $this->toDateTimeImmutable($model->getAttribute('last_touched_at')),
            );
        }

        return new VisitsListView(
            currentPage: $page,
            perPage: $perPage,
            total: $total,
            lastPage: max(1, (int) ceil($total / $perPage)),
            items: $items,
        );
    }

    private function applyFilters(Builder $visitQuery, ListVisitsQuery $query): void
    {
        if ($query->visitorId !== null) {
            $visitQuery->where('visitor_id', $query->visitorId);
        }

        if ($query->firstAttributionSource !== null) {
            $visitQuery->where('first_attribution_source', $query->firstAttributionSource);
        }

        if ($query->firstAttributionMedium !== null) {
            $visitQuery->where('first_attribution_medium', $query->firstAttributionMedium);
        }

        if ($query->lastAttributionSource !== null) {
            $visitQuery->where('last_attribution_source', $query->lastAttributionSource);
        }

        if ($query->lastAttributionMedium !== null) {
            $visitQuery->where('last_attribution_medium', $query->lastAttributionMedium);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if (!$value instanceof DateTimeInterface) {
            throw new UnexpectedValueException('Expected a date/time value from VisitModel.');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
