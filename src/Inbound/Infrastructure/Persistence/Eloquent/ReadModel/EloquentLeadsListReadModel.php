<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Inbound\Application\Queries\Backoffice\ListLeads\LeadListItemView;
use Inbound\Application\Queries\Backoffice\ListLeads\LeadsListReadModel;
use Inbound\Application\Queries\Backoffice\ListLeads\LeadsListView;
use Inbound\Application\Queries\Backoffice\ListLeads\ListLeadsQuery;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use UnexpectedValueException;

final class EloquentLeadsListReadModel implements LeadsListReadModel
{
    public function __invoke(ListLeadsQuery $query): LeadsListView
    {
        $page = max(1, $query->page);
        $perPage = max(1, $query->perPage);

        $leadQuery = LeadModel::query();
        $this->applyFilters($leadQuery, $query);

        $total = (clone $leadQuery)->count();

        /** @var list<LeadModel> $models */
        $models = $leadQuery
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->forPage($page, $perPage)
            ->get()
            ->all();

        $items = [];

        foreach ($models as $model) {
            $status = $model->getAttribute('status');
            $leadStatus = $status instanceof LeadStatus
                ? $status
                : LeadStatus::from((string) $status);

            $items[] = new LeadListItemView(
                leadId: (string) $model->getAttribute('id'),
                shortLeadId: $this->shortLeadId((string) $model->getAttribute('id')),
                visitorId: $this->nullableString($model->getAttribute('visitor_id')),
                visitId: $this->nullableString($model->getAttribute('visit_id')),
                name: $this->nullableString($model->getAttribute('name')),
                phone: $this->nullableString($model->getAttribute('phone')),
                status: $leadStatus->value,
                statusLabel: $leadStatus->label(),
                origin: (string) $model->getAttribute('origin'),
                originLabel: $this->originLabel((string) $model->getAttribute('origin')),
                attributionSource: $this->nullableString($model->getAttribute('attribution_source')),
                attributionMedium: $this->nullableString($model->getAttribute('attribution_medium')),
                createdAt: $this->toDateTimeImmutable($model->getAttribute('created_at')),
            );
        }

        return new LeadsListView(
            currentPage: $page,
            perPage: $perPage,
            total: $total,
            lastPage: max(1, (int) ceil($total / $perPage)),
            items: $items,
        );
    }

    private function applyFilters(Builder $leadQuery, ListLeadsQuery $query): void
    {
        if ($query->status !== null) {
            $leadQuery->where('status', $query->status->value);
        }

        if ($query->origin !== null) {
            $leadQuery->where('origin', $query->origin);
        }

        if ($query->attributionSource !== null) {
            $leadQuery->where('attribution_source', $query->attributionSource);
        }

        if ($query->attributionMedium !== null) {
            $leadQuery->where('attribution_medium', $query->attributionMedium);
        }
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function shortLeadId(string $leadId): string
    {
        $segments = explode('-', $leadId);

        if (count($segments) < 2) {
            return $leadId;
        }

        return implode('-', array_slice($segments, 0, 2));
    }

    private function originLabel(string $origin): string
    {
        return match ($origin) {
            'form' => 'Форма',
            'phone_click' => 'Клік по телефону',
            'messenger_click' => 'Клік по месенджеру',
            default => $origin,
        };
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if (!$value instanceof DateTimeInterface) {
            throw new UnexpectedValueException('Expected a date/time value from LeadModel.');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
