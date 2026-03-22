<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use DateTimeInterface;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Touch\Touch;
use Inbound\Domain\Touch\TouchId;
use Inbound\Domain\Touch\TouchRepository;
use Inbound\Domain\Touch\TouchType;
use Inbound\Domain\Visit\VisitId;

final class EloquentTouchRepository implements TouchRepository
{
    public function save(Touch $touch): void
    {
        $model = TouchModel::query()->find($touch->id()->value());

        if (!$model instanceof TouchModel) {
            $model = new TouchModel();
        }

        $model->fill($this->mapTouchToAttributes($touch));
        $model->save();
    }

    public function findById(TouchId $id): ?Touch
    {
        $model = TouchModel::query()->find($id->value());

        if (!$model instanceof TouchModel) {
            return null;
        }

        return $this->mapModelToTouch($model);
    }

    /**
     * @return array<string, DateTimeImmutable|TouchType|string>
     */
    private function mapTouchToAttributes(Touch $touch): array
    {
        return [
            'id' => $touch->id()->value(),
            'visit_id' => $touch->visitId()->value(),
            'visitor_id' => $touch->visitorId()->value(),
            'type' => $touch->type(),
            'occurred_at' => $touch->occurredAt(),
        ];
    }

    private function mapModelToTouch(TouchModel $model): Touch
    {
        $type = $model->getAttribute('type');

        return new Touch(
            new TouchId((string) $model->getAttribute('id')),
            new VisitId((string) $model->getAttribute('visit_id')),
            new VisitorId((string) $model->getAttribute('visitor_id')),
            $type instanceof TouchType ? $type : TouchType::from((string) $type),
            $this->toDateTimeImmutable($model->getAttribute('occurred_at')),
        );
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if (!$value instanceof DateTimeInterface) {
            throw new \UnexpectedValueException('Expected a date/time value from TouchModel.');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
