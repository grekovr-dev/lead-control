<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use DateTimeInterface;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Lead\LeadStatus;
use Inbound\Domain\LeadStatusHistory\LeadStatusTransition;
use Inbound\Domain\LeadStatusHistory\LeadStatusTransitionRepository;
use UnexpectedValueException;

final class EloquentLeadStatusTransitionRepository implements LeadStatusTransitionRepository
{
    public function save(LeadStatusTransition $transition): void
    {
        $model = new LeadStatusTransitionModel();
        $model->fill([
            'lead_id' => $transition->leadId()->value(),
            'from_status' => $transition->fromStatus(),
            'to_status' => $transition->toStatus(),
            'rule_key' => $transition->ruleKey(),
            'changed_at' => $transition->changedAt(),
        ]);
        $model->save();
    }

    public function findByLeadId(LeadId $leadId): array
    {
        /** @var list<LeadStatusTransitionModel> $models */
        $models = LeadStatusTransitionModel::query()
            ->where('lead_id', $leadId->value())
            ->orderBy('changed_at')
            ->orderBy('id')
            ->get()
            ->all();

        return array_map($this->mapModelToTransition(...), $models);
    }

    private function mapModelToTransition(LeadStatusTransitionModel $model): LeadStatusTransition
    {
        $fromStatus = $model->getAttribute('from_status');
        $toStatus = $model->getAttribute('to_status');

        return new LeadStatusTransition(
            new LeadId((string) $model->getAttribute('lead_id')),
            $fromStatus instanceof LeadStatus ? $fromStatus : LeadStatus::from((string) $fromStatus),
            $toStatus instanceof LeadStatus ? $toStatus : LeadStatus::from((string) $toStatus),
            (string) $model->getAttribute('rule_key'),
            $this->toDateTimeImmutable($model->getAttribute('changed_at')),
        );
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if (!$value instanceof DateTimeInterface) {
            throw new UnexpectedValueException('Expected a date/time value from LeadStatusTransitionModel.');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
