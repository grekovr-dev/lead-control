<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent;

use DateTimeImmutable;
use DateTimeInterface;
use Inbound\Domain\Click\Click;
use Inbound\Domain\Click\ClickId;
use Inbound\Domain\Click\ClickRepository;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\VisitId;

final class EloquentClickRepository implements ClickRepository
{
    public function save(Click $click): void
    {
        $model = ClickModel::query()->find($click->id()->value());

        if (! $model instanceof ClickModel) {
            $model = new ClickModel;
        }

        $model->fill($this->mapClickToAttributes($click));
        $model->save();
    }

    public function findById(ClickId $id): ?Click
    {
        $model = ClickModel::query()->find($id->value());

        if (! $model instanceof ClickModel) {
            return null;
        }

        return $this->mapModelToClick($model);
    }

    /**
     * @return array<string, DateTimeImmutable|string|null>
     */
    private function mapClickToAttributes(Click $click): array
    {
        return [
            'id' => $click->id()->value(),
            'visitor_id' => $click->visitorId()->value(),
            'visit_id' => $click->visitId()?->value(),
            'landing_url' => $click->landingUrl(),
            'occurred_at' => $click->occurredAt(),
            ...$this->mapAttributionToAttributes($click->attribution()),
        ];
    }

    private function mapModelToClick(ClickModel $model): Click
    {
        $visitId = $this->nullableString($model->getAttribute('visit_id'));

        return new Click(
            new ClickId((string) $model->getAttribute('id')),
            new VisitorId((string) $model->getAttribute('visitor_id')),
            $this->mapAttributesToAttribution($model),
            (string) $model->getAttribute('landing_url'),
            $this->toDateTimeImmutable($model->getAttribute('occurred_at')),
            $visitId !== null ? new VisitId($visitId) : null,
        );
    }

    /**
     * @return array<string, string|null>
     */
    private function mapAttributionToAttributes(Attribution $attribution): array
    {
        return [
            'attribution_referrer' => $attribution->referrer(),
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

    private function mapAttributesToAttribution(ClickModel $model): Attribution
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
            $model->getAttribute('attribution_referrer'),
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if (! $value instanceof DateTimeInterface) {
            throw new \UnexpectedValueException('Expected a date/time value from ClickModel.');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
