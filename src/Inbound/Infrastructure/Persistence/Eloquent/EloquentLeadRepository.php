<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use DateTimeInterface;
use Inbound\Domain\Lead\Lead;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Lead\LeadRepository;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\VisitId;

final class EloquentLeadRepository implements LeadRepository
{
    public function save(Lead $lead): void
    {
        $model = LeadModel::query()->find($lead->id()->value());

        if (!$model instanceof LeadModel) {
            $model = new LeadModel();
        }

        $model->fill($this->mapLeadToAttributes($lead));
        $model->save();
    }

    public function findById(LeadId $id): ?Lead
    {
        $model = LeadModel::query()->find($id->value());

        if (!$model instanceof LeadModel) {
            return null;
        }

        return $this->mapModelToLead($model);
    }

    /**
     * @return array<string, DateTimeImmutable|LeadStatus|string|null>
     */
    private function mapLeadToAttributes(Lead $lead): array
    {
        return [
            'id' => $lead->id()->value(),
            'visitor_id' => $lead->visitorId()->value(),
            'visit_id' => $lead->visitId()->value(),
            'name' => $lead->name(),
            'phone' => $lead->phone(),
            'status' => $lead->status(),
            'origin' => $lead->origin(),
            'created_at' => $lead->createdAt(),
            ...$this->mapAttributionToAttributes($lead->attribution()),
        ];
    }

    private function mapModelToLead(LeadModel $model): Lead
    {
        $status = $model->getAttribute('status');

        return new Lead(
            new LeadId((string) $model->getAttribute('id')),
            new VisitorId((string) $model->getAttribute('visitor_id')),
            new VisitId((string) $model->getAttribute('visit_id')),
            $model->getAttribute('name'),
            $model->getAttribute('phone'),
            $this->mapAttributesToAttribution($model),
            $status instanceof LeadStatus ? $status : LeadStatus::from((string) $status),
            (string) $model->getAttribute('origin'),
            $this->toDateTimeImmutable($model->getAttribute('created_at')),
        );
    }

    /**
     * @return array<string, string|null>
     */
    private function mapAttributionToAttributes(Attribution $attribution): array
    {
        return [
            'attribution_source' => $attribution->source(),
            'attribution_medium' => $attribution->medium(),
            'attribution_campaign' => $attribution->campaign(),
            'attribution_content' => $attribution->content(),
            'attribution_term' => $attribution->term(),
            'attribution_gclid' => $attribution->gclid(),
            'attribution_fbclid' => $attribution->fbclid(),
            'attribution_msclkid' => $attribution->msclkid(),
        ];
    }

    private function mapAttributesToAttribution(LeadModel $model): Attribution
    {
        return new Attribution(
            $model->getAttribute('attribution_source'),
            $model->getAttribute('attribution_medium'),
            $model->getAttribute('attribution_campaign'),
            $model->getAttribute('attribution_content'),
            $model->getAttribute('attribution_term'),
            $model->getAttribute('attribution_gclid'),
            $model->getAttribute('attribution_fbclid'),
            $model->getAttribute('attribution_msclkid'),
        );
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if (!$value instanceof DateTimeInterface) {
            throw new \UnexpectedValueException('Expected a date/time value from LeadModel.');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
