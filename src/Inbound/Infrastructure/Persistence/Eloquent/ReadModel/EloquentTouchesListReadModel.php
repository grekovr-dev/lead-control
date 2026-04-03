<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Inbound\Application\Queries\Backoffice\ListTouches\ListTouchesQuery;
use Inbound\Application\Queries\Backoffice\ListTouches\TouchListItemView;
use Inbound\Application\Queries\Backoffice\ListTouches\TouchesListReadModel;
use Inbound\Application\Queries\Backoffice\ListTouches\TouchesListView;
use Inbound\Domain\Touch\TouchType;
use Inbound\Infrastructure\Persistence\Eloquent\TouchModel;
use UnexpectedValueException;

final class EloquentTouchesListReadModel implements TouchesListReadModel
{
    public function __invoke(ListTouchesQuery $query): TouchesListView
    {
        $page = max(1, $query->page);
        $perPage = max(1, $query->perPage);

        $touchQuery = TouchModel::query();
        $this->applyFilters($touchQuery, $query);

        $total = (clone $touchQuery)->count();

        /** @var list<TouchModel> $models */
        $models = $touchQuery
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get()
            ->all();

        $items = [];

        foreach ($models as $model) {
            $type = $model->getAttribute('type');
            $touchType = $type instanceof TouchType
                ? $type
                : TouchType::from((string) $type);

            $items[] = new TouchListItemView(
                touchId: (string) $model->getAttribute('id'),
                visitId: (string) $model->getAttribute('visit_id'),
                visitorId: (string) $model->getAttribute('visitor_id'),
                type: $touchType->value,
                typeLabel: $this->touchTypeLabel($touchType),
                occurredAt: $this->toDateTimeImmutable($model->getAttribute('occurred_at')),
            );
        }

        return new TouchesListView(
            currentPage: $page,
            perPage: $perPage,
            total: $total,
            lastPage: max(1, (int) ceil($total / $perPage)),
            items: $items,
        );
    }

    private function applyFilters(Builder $touchQuery, ListTouchesQuery $query): void
    {
        if ($query->visitId !== null) {
            $touchQuery->where('visit_id', $query->visitId);
        }

        if ($query->visitorId !== null) {
            $touchQuery->where('visitor_id', $query->visitorId);
        }

        if ($query->type !== null) {
            $touchQuery->where('type', $query->type->value);
        }
    }

    private function touchTypeLabel(TouchType $type): string
    {
        return match ($type) {
            TouchType::PhoneClick => 'Клік по телефону',
            TouchType::LeadFormClick => 'Клік по формі',
            TouchType::MessengerClick => 'Клік по месенджеру',
            TouchType::WorksClick => 'Клік по роботах',
        };
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if (!$value instanceof DateTimeInterface) {
            throw new UnexpectedValueException('Expected a date/time value from TouchModel.');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
