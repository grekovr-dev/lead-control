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

        if (! $model instanceof LeadModel) {
            $model = new LeadModel;
        }

        $model->fill($this->mapLeadToAttributes($lead));
        $model->save();
    }

    public function findById(LeadId $id): ?Lead
    {
        $model = LeadModel::query()->find($id->value());

        if (! $model instanceof LeadModel) {
            return null;
        }

        return $this->mapModelToLead($model);
    }

    public function findByVisitIdAndOrigin(VisitId $visitId, string $origin): ?Lead
    {
        $model = LeadModel::query()
            ->where('visit_id', $visitId->value())
            ->where('origin', $origin)
            ->first();

        if (! $model instanceof LeadModel) {
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
            'landing_url' => $lead->landingUrl(),
            'created_at' => $lead->createdAt(),
            ...$this->mapAttributionToAttributes($lead->visitAttribution(), 'visit_attribution'),
            ...$this->mapAttributionToAttributes($lead->visitorAttribution(), 'visitor_attribution'),
        ];
    }

    private function mapModelToLead(LeadModel $model): Lead
    {
        $status = $model->getAttribute('status');
        $visitAttribution = $this->mapAttributesToAttribution($model, 'visit_attribution');
        $visitorAttribution = $this->mapAttributesToAttribution($model, 'visitor_attribution');

        return new Lead(
            new LeadId((string) $model->getAttribute('id')),
            new VisitorId((string) $model->getAttribute('visitor_id')),
            new VisitId((string) $model->getAttribute('visit_id')),
            $model->getAttribute('name'),
            $model->getAttribute('phone'),
            $visitAttribution,
            $status instanceof LeadStatus ? $status : LeadStatus::from((string) $status),
            (string) $model->getAttribute('origin'),
            $this->toDateTimeImmutable($model->getAttribute('created_at')),
            $visitorAttribution,
            $this->nullableString($model->getAttribute('landing_url')),
        );
    }

    /**
     * @return array<string, string|null>
     */
    private function mapAttributionToAttributes(Attribution $attribution, string $prefix): array
    {
        return [
            $prefix.'_source' => $attribution->source(),
            $prefix.'_medium' => $attribution->medium(),
            $prefix.'_campaign' => $attribution->campaign(),
            $prefix.'_content' => $attribution->content(),
            $prefix.'_term' => $attribution->term(),
            $prefix.'_gclid' => $attribution->gclid(),
            $prefix.'_fbclid' => $attribution->fbclid(),
            $prefix.'_msclkid' => $attribution->msclkid(),
            $prefix.'_referrer' => $attribution->referrer(),
        ];
    }

    private function mapAttributesToAttribution(LeadModel $model, string $prefix): Attribution
    {
        return new Attribution(
            $model->getAttribute($prefix.'_source'),
            $model->getAttribute($prefix.'_medium'),
            $model->getAttribute($prefix.'_campaign'),
            $model->getAttribute($prefix.'_content'),
            $model->getAttribute($prefix.'_term'),
            $model->getAttribute($prefix.'_gclid'),
            $model->getAttribute($prefix.'_fbclid'),
            $model->getAttribute($prefix.'_msclkid'),
            $model->getAttribute($prefix.'_referrer'),
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if (! $value instanceof DateTimeInterface) {
            throw new \UnexpectedValueException('Expected a date/time value from LeadModel.');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
