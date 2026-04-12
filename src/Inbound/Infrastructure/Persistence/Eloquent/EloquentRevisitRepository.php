<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use DateTimeInterface;
use Inbound\Domain\Revisit\Revisit;
use Inbound\Domain\Revisit\RevisitId;
use Inbound\Domain\Revisit\RevisitRepository;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\VisitId;

final class EloquentRevisitRepository implements RevisitRepository
{
    public function save(Revisit $revisit): void
    {
        $model = RevisitModel::query()->find($revisit->id()->value());

        if (! $model instanceof RevisitModel) {
            $model = new RevisitModel;
        }

        $model->fill($this->mapRevisitToAttributes($revisit));
        $model->save();
    }

    public function findById(RevisitId $id): ?Revisit
    {
        $model = RevisitModel::query()->find($id->value());

        if (! $model instanceof RevisitModel) {
            return null;
        }

        return $this->mapModelToRevisit($model);
    }

    /**
     * @return array<string, DateTimeImmutable|string>
     */
    private function mapRevisitToAttributes(Revisit $revisit): array
    {
        return [
            'id' => $revisit->id()->value(),
            'visitor_id' => $revisit->visitorId()->value(),
            'visit_id' => $revisit->visitId()->value(),
            'landing_url' => $revisit->landingUrl(),
            'occurred_at' => $revisit->occurredAt(),
        ];
    }

    private function mapModelToRevisit(RevisitModel $model): Revisit
    {
        return new Revisit(
            new RevisitId((string) $model->getAttribute('id')),
            new VisitorId((string) $model->getAttribute('visitor_id')),
            new VisitId((string) $model->getAttribute('visit_id')),
            (string) $model->getAttribute('landing_url'),
            $this->toDateTimeImmutable($model->getAttribute('occurred_at')),
        );
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if (! $value instanceof DateTimeInterface) {
            throw new \UnexpectedValueException('Expected a date/time value from RevisitModel.');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
