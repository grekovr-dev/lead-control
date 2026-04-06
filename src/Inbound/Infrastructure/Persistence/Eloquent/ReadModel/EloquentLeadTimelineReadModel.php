<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use App\Models\User;
use DateTimeImmutable;
use DateTimeInterface;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\GetLeadTimelineQuery;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\LeadTimelineEventView;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\LeadTimelineNotFoundException;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\LeadTimelineReadModel;
use Inbound\Application\Queries\Backoffice\GetLeadTimeline\LeadTimelineView;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\Touch\TouchType;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadNoteModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadStatusTransitionModel;
use Inbound\Infrastructure\Persistence\Eloquent\TouchModel;
use UnexpectedValueException;

final class EloquentLeadTimelineReadModel implements LeadTimelineReadModel
{
    public function __invoke(GetLeadTimelineQuery $query): LeadTimelineView
    {
        $leadModel = LeadModel::query()->find($query->leadId->value());

        if (! $leadModel instanceof LeadModel) {
            throw new LeadTimelineNotFoundException(sprintf(
                'Lead timeline not found for lead id "%s".',
                $query->leadId->value(),
            ));
        }

        $events = [
            $this->buildLeadCreatedEvent($leadModel),
            ...$this->buildTouchEvents($leadModel),
            ...$this->buildClickEvents($leadModel),
            ...$this->buildStatusTransitionEvents($leadModel),
            ...$this->buildLeadNoteEvents($leadModel),
        ];

        return new LeadTimelineView(
            leadId: (string) $leadModel->getAttribute('id'),
            events: $this->sortEvents($events),
        );
    }

    private function buildLeadCreatedEvent(LeadModel $leadModel): LeadTimelineEventView
    {
        $origin = (string) $leadModel->getAttribute('origin');

        return new LeadTimelineEventView(
            type: 'lead_created',
            occurredAt: $this->toDateTimeImmutable($leadModel->getAttribute('created_at')),
            title: 'Лід створено',
            description: $this->leadOriginLabel($origin),
            origin: $origin,
            originLabel: $this->leadOriginLabel($origin),
        );
    }

    /**
     * @return list<LeadTimelineEventView>
     */
    private function buildTouchEvents(LeadModel $leadModel): array
    {
        $visitId = $this->nullableString($leadModel->getAttribute('visit_id'));

        if ($visitId === null) {
            return [];
        }

        /** @var list<TouchModel> $models */
        $models = TouchModel::query()
            ->where('visit_id', $visitId)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get()
            ->all();

        $events = [];

        foreach ($models as $model) {
            $type = $model->getAttribute('type');
            $touchType = $type instanceof TouchType
                ? $type
                : TouchType::from((string) $type);

            $events[] = new LeadTimelineEventView(
                type: 'touch',
                occurredAt: $this->toDateTimeImmutable($model->getAttribute('occurred_at')),
                title: $this->touchTypeLabel($touchType),
                description: null,
                touchType: $touchType->value,
                touchTypeLabel: $this->touchTypeLabel($touchType),
            );
        }

        return $events;
    }

    /**
     * @return list<LeadTimelineEventView>
     */
    private function buildClickEvents(LeadModel $leadModel): array
    {
        $visitorId = $this->nullableString($leadModel->getAttribute('visitor_id'));

        if ($visitorId === null) {
            return [];
        }

        /** @var list<ClickModel> $models */
        $models = ClickModel::query()
            ->where('visitor_id', $visitorId)
            ->orderBy('occurred_at')
            ->orderBy('id')
            ->get()
            ->all();

        $events = [];

        foreach ($models as $model) {
            $events[] = new LeadTimelineEventView(
                type: 'click',
                occurredAt: $this->toDateTimeImmutable($model->getAttribute('occurred_at')),
                title: 'Клік по лендингу',
                description: $this->nullableString($model->getAttribute('landing_url')),
                landingUrl: $this->nullableString($model->getAttribute('landing_url')),
                referrer: $this->nullableString($model->getAttribute('attribution_referrer')),
            );
        }

        return $events;
    }

    /**
     * @return list<LeadTimelineEventView>
     */
    private function buildStatusTransitionEvents(LeadModel $leadModel): array
    {
        $leadId = (string) $leadModel->getAttribute('id');

        /** @var list<LeadStatusTransitionModel> $models */
        $models = LeadStatusTransitionModel::query()
            ->where('lead_id', $leadId)
            ->orderBy('changed_at')
            ->orderBy('id')
            ->get()
            ->all();

        $events = [];

        foreach ($models as $model) {
            $fromStatus = $model->getAttribute('from_status');
            $toStatus = $model->getAttribute('to_status');
            $from = $fromStatus instanceof LeadStatus
                ? $fromStatus
                : LeadStatus::from((string) $fromStatus);
            $to = $toStatus instanceof LeadStatus
                ? $toStatus
                : LeadStatus::from((string) $toStatus);

            $events[] = new LeadTimelineEventView(
                type: 'status_transition',
                occurredAt: $this->toDateTimeImmutable($model->getAttribute('changed_at')),
                title: 'Статус змінено',
                description: sprintf('%s -> %s', $from->label(), $to->label()),
                fromStatus: $from->value,
                fromStatusLabel: $from->label(),
                toStatus: $to->value,
                toStatusLabel: $to->label(),
                ruleKey: (string) $model->getAttribute('rule_key'),
            );
        }

        return $events;
    }

    /**
     * @return list<LeadTimelineEventView>
     */
    private function buildLeadNoteEvents(LeadModel $leadModel): array
    {
        $leadId = (string) $leadModel->getAttribute('id');

        /** @var list<LeadNoteModel> $models */
        $models = LeadNoteModel::query()
            ->with(['author:id,name,email'])
            ->where('lead_id', $leadId)
            ->orderBy('created_at')
            ->orderBy('id')
            ->get()
            ->all();

        $events = [];

        foreach ($models as $model) {
            $events[] = new LeadTimelineEventView(
                type: 'lead_note',
                occurredAt: $this->toDateTimeImmutable($model->getAttribute('created_at')),
                title: 'Додано нотатку',
                description: (string) $model->getAttribute('note'),
                authorId: $this->nullablePositiveInt($model->getAttribute('author_id')),
                authorLabel: $this->authorLabel($model),
            );
        }

        return $events;
    }

    /**
     * @param  list<LeadTimelineEventView>  $events
     * @return list<LeadTimelineEventView>
     */
    private function sortEvents(array $events): array
    {
        $indexedEvents = [];

        foreach ($events as $index => $event) {
            $indexedEvents[] = [
                'event' => $event,
                'index' => $index,
            ];
        }

        usort($indexedEvents, function (array $left, array $right): int {
            /** @var LeadTimelineEventView $leftEvent */
            $leftEvent = $left['event'];
            /** @var LeadTimelineEventView $rightEvent */
            $rightEvent = $right['event'];

            $leftTime = $leftEvent->occurredAt->format('U.u');
            $rightTime = $rightEvent->occurredAt->format('U.u');

            if ($leftTime !== $rightTime) {
                return $rightTime <=> $leftTime;
            }

            $priority = $this->eventPriority($leftEvent->type) <=> $this->eventPriority($rightEvent->type);

            if ($priority !== 0) {
                return $priority;
            }

            return $left['index'] <=> $right['index'];
        });

        return array_map(
            static fn (array $item): LeadTimelineEventView => $item['event'],
            $indexedEvents,
        );
    }

    private function eventPriority(string $type): int
    {
        return match ($type) {
            'click' => 10,
            'touch' => 20,
            'lead_created' => 30,
            'status_transition' => 40,
            'lead_note' => 50,
            default => 100,
        };
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

    private function leadOriginLabel(string $origin): string
    {
        return match ($origin) {
            'form' => 'Форма',
            'phone_click' => 'Клік по телефону',
            'messenger_click' => 'Клік по месенджеру',
            default => $origin,
        };
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function authorLabel(LeadNoteModel $model): ?string
    {
        $author = $model->author;

        if (! $author instanceof User) {
            $authorId = $this->nullablePositiveInt($model->getAttribute('author_id'));

            return $authorId !== null ? sprintf('Автор #%d', $authorId) : null;
        }

        $name = trim((string) $author->getAttribute('name'));

        if ($name !== '') {
            return $name;
        }

        $email = trim((string) $author->getAttribute('email'));

        if ($email !== '') {
            return $email;
        }

        $authorId = $this->nullablePositiveInt($model->getAttribute('author_id'));

        return $authorId !== null ? sprintf('Автор #%d', $authorId) : null;
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_numeric($value)) {
            $value = (int) $value;

            return $value > 0 ? $value : null;
        }

        return null;
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if (! $value instanceof DateTimeInterface) {
            throw new UnexpectedValueException('Expected a date/time value from Eloquent model.');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
