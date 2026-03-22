<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use DateTimeInterface;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\Visit;
use Inbound\Domain\Visit\VisitId;
use Inbound\Domain\Visit\VisitRepository;

final class EloquentVisitRepository implements VisitRepository
{
    public function save(Visit $visit): void
    {
        $model = VisitModel::query()->find($visit->id()->value());

        if (!$model instanceof VisitModel) {
            $model = new VisitModel();
        }

        $model->fill($this->mapVisitToAttributes($visit));
        $model->save();
    }

    public function findById(VisitId $id): ?Visit
    {
        $model = VisitModel::query()->find($id->value());

        if (!$model instanceof VisitModel) {
            return null;
        }

        return $this->mapModelToVisit($model);
    }

    public function findLastByVisitorId(VisitorId $visitorId): ?Visit
    {
        $model = VisitModel::query()
            ->where('visitor_id', $visitorId->value())
            ->orderByDesc('last_touched_at')
            ->first();

        if (!$model instanceof VisitModel) {
            return null;
        }

        return $this->mapModelToVisit($model);
    }

    /**
     * @return array<string, DateTimeImmutable|string|null>
     */
    private function mapVisitToAttributes(Visit $visit): array
    {
        return [
            'id' => $visit->id()->value(),
            'visitor_id' => $visit->visitorId()->value(),
            'started_at' => $visit->startedAt(),
            'last_touched_at' => $visit->lastTouchedAt(),
            ...$this->mapAttributionToAttributes($visit->firstAttribution(), 'first_attribution'),
            ...$this->mapAttributionToAttributes($visit->lastAttribution(), 'last_attribution'),
        ];
    }

    private function mapModelToVisit(VisitModel $model): Visit
    {
        return new Visit(
            new VisitId((string) $model->getAttribute('id')),
            new VisitorId((string) $model->getAttribute('visitor_id')),
            $this->mapAttributesToAttribution($model, 'first_attribution'),
            $this->mapAttributesToAttribution($model, 'last_attribution'),
            $this->toDateTimeImmutable($model->getAttribute('started_at')),
            $this->toDateTimeImmutable($model->getAttribute('last_touched_at')),
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
        ];
    }

    private function mapAttributesToAttribution(VisitModel $model, string $prefix): Attribution
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
        );
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if (!$value instanceof DateTimeInterface) {
            throw new \UnexpectedValueException('Expected a date/time value from VisitModel.');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
