<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use DateTimeImmutable;
use DateTimeInterface;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\ActivitySummaryView;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\AttributionSnapshotView;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\GetLeadDetailsQuery;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\LeadCoreView;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\LeadDetailsNotFoundException;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\LeadDetailsReadModel;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\LeadDetailsView;
use Inbound\Application\Queries\Backoffice\GetLeadDetails\LeadVisitSummaryView;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Infrastructure\Persistence\Eloquent\ClickModel;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use Inbound\Infrastructure\Persistence\Eloquent\TouchModel;
use Inbound\Infrastructure\Persistence\Eloquent\VisitModel;
use UnexpectedValueException;

final class EloquentLeadDetailsReadModel implements LeadDetailsReadModel
{
    public function __invoke(GetLeadDetailsQuery $query): LeadDetailsView
    {
        $leadModel = LeadModel::query()->find($query->leadId->value());

        if (!$leadModel instanceof LeadModel) {
            throw new LeadDetailsNotFoundException(sprintf(
                'Lead details not found for lead id "%s".',
                $query->leadId->value(),
            ));
        }

        $leadCore = $this->mapLeadCore($leadModel);
        $visit = $this->resolveVisitSummary($leadModel);

        return new LeadDetailsView(
            lead: $leadCore,
            visit: $visit,
            preLeadTouchSummary: $this->buildPreLeadTouchSummary($leadModel),
            preLeadVisitorClickSummary: $this->buildPreLeadVisitorClickSummary($leadModel),
        );
    }

    private function mapLeadCore(LeadModel $leadModel): LeadCoreView
    {
        $status = $leadModel->getAttribute('status');
        $leadStatus = $status instanceof LeadStatus
            ? $status
            : LeadStatus::from((string) $status);

        return new LeadCoreView(
            leadId: (string) $leadModel->getAttribute('id'),
            visitorId: $this->nullableString($leadModel->getAttribute('visitor_id')),
            visitId: $this->nullableString($leadModel->getAttribute('visit_id')),
            name: $this->nullableString($leadModel->getAttribute('name')),
            phone: $this->nullableString($leadModel->getAttribute('phone')),
            status: $leadStatus->value,
            statusLabel: $leadStatus->label(),
            origin: (string) $leadModel->getAttribute('origin'),
            originLabel: $this->originLabel((string) $leadModel->getAttribute('origin')),
            createdAt: $this->toDateTimeImmutable($leadModel->getAttribute('created_at')),
            attribution: $this->mapAttributionSnapshot($leadModel, 'attribution'),
        );
    }

    private function resolveVisitSummary(LeadModel $leadModel): ?LeadVisitSummaryView
    {
        $visitId = $this->nullableString($leadModel->getAttribute('visit_id'));

        if ($visitId === null) {
            return null;
        }

        $visitModel = VisitModel::query()->find($visitId);

        if (!$visitModel instanceof VisitModel) {
            return null;
        }

        return new LeadVisitSummaryView(
            visitId: (string) $visitModel->getAttribute('id'),
            visitorId: (string) $visitModel->getAttribute('visitor_id'),
            startedAt: $this->toDateTimeImmutable($visitModel->getAttribute('started_at')),
            lastTouchedAt: $this->toDateTimeImmutable($visitModel->getAttribute('last_touched_at')),
            firstAttribution: $this->mapAttributionSnapshot($visitModel, 'first_attribution'),
            lastAttribution: $this->mapAttributionSnapshot($visitModel, 'last_attribution'),
        );
    }

    private function buildPreLeadTouchSummary(LeadModel $leadModel): ActivitySummaryView
    {
        $visitId = $this->nullableString($leadModel->getAttribute('visit_id'));

        if ($visitId === null) {
            return new ActivitySummaryView(0, null);
        }

        $leadCreatedAt = $this->toDateTimeImmutable($leadModel->getAttribute('created_at'));

        $touchQuery = TouchModel::query()
            ->where('visit_id', $visitId)
            ->where('occurred_at', '<=', $leadCreatedAt);

        /** @var ?TouchModel $lastTouchModel */
        $lastTouchModel = (clone $touchQuery)
            ->orderByDesc('occurred_at')
            ->first();

        return new ActivitySummaryView(
            count: $touchQuery->count(),
            lastOccurredAt: $this->nullableDateTimeImmutable($lastTouchModel?->getAttribute('occurred_at')),
        );
    }

    private function buildPreLeadVisitorClickSummary(LeadModel $leadModel): ActivitySummaryView
    {
        $visitorId = $this->nullableString($leadModel->getAttribute('visitor_id'));

        if ($visitorId === null) {
            return new ActivitySummaryView(0, null);
        }

        $leadCreatedAt = $this->toDateTimeImmutable($leadModel->getAttribute('created_at'));

        $clickQuery = ClickModel::query()
            ->where('visitor_id', $visitorId)
            ->where('occurred_at', '<=', $leadCreatedAt);

        /** @var ?ClickModel $lastClickModel */
        $lastClickModel = (clone $clickQuery)
            ->orderByDesc('occurred_at')
            ->first();

        return new ActivitySummaryView(
            count: $clickQuery->count(),
            lastOccurredAt: $this->nullableDateTimeImmutable($lastClickModel?->getAttribute('occurred_at')),
        );
    }

    private function mapAttributionSnapshot(object $model, string $prefix): AttributionSnapshotView
    {
        return new AttributionSnapshotView(
            source: $this->nullableString($model->getAttribute($prefix.'_source')),
            medium: $this->nullableString($model->getAttribute($prefix.'_medium')),
            campaign: $this->nullableString($model->getAttribute($prefix.'_campaign')),
            content: $this->nullableString($model->getAttribute($prefix.'_content')),
            term: $this->nullableString($model->getAttribute($prefix.'_term')),
            gclid: $this->nullableString($model->getAttribute($prefix.'_gclid')),
            fbclid: $this->nullableString($model->getAttribute($prefix.'_fbclid')),
            msclkid: $this->nullableString($model->getAttribute($prefix.'_msclkid')),
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
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

    private function nullableDateTimeImmutable(mixed $value): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        return $this->toDateTimeImmutable($value);
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if (!$value instanceof DateTimeInterface) {
            throw new UnexpectedValueException('Expected a date/time value from Eloquent model.');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
