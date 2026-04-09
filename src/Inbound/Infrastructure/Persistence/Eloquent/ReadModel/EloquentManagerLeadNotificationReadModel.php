<?php

declare(strict_types=1);

namespace Inbound\Infrastructure\Persistence\Eloquent\ReadModel;

use DateTimeImmutable;
use DateTimeInterface;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\GetManagerLeadNotificationQuery;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\ManagerLeadNotificationNotFoundException;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\ManagerLeadNotificationReadModel;
use Inbound\Application\Queries\Notifications\GetManagerLeadNotification\ManagerLeadNotificationView;
use Inbound\Infrastructure\Persistence\Eloquent\LeadModel;
use UnexpectedValueException;

final class EloquentManagerLeadNotificationReadModel implements ManagerLeadNotificationReadModel
{
    public function __invoke(GetManagerLeadNotificationQuery $query): ManagerLeadNotificationView
    {
        $leadModel = LeadModel::query()->find($query->leadId->value());

        if (! ($leadModel instanceof LeadModel)) {
            throw new ManagerLeadNotificationNotFoundException(sprintf(
                'Manager lead notification not found for lead id "%s".',
                $query->leadId->value(),
            ));
        }

        return new ManagerLeadNotificationView(
            leadId: (string) $leadModel->getAttribute('id'),
            name: $this->nullableString($leadModel->getAttribute('name')),
            phone: $this->nullableString($leadModel->getAttribute('phone')),
            origin: (string) $leadModel->getAttribute('origin'),
            landingUrl: $this->nullableString($leadModel->getAttribute('landing_url')),
            createdAt: $this->toDateTimeImmutable($leadModel->getAttribute('created_at')),
        );
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function toDateTimeImmutable(mixed $value): DateTimeImmutable
    {
        if (! ($value instanceof DateTimeInterface)) {
            throw new UnexpectedValueException('Expected a date/time value from Eloquent model.');
        }

        return DateTimeImmutable::createFromInterface($value);
    }
}
