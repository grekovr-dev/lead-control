<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Notifications\GetManagerLeadNotification;

use DateTimeImmutable;

final readonly class ManagerLeadNotificationView
{
    public function __construct(
        public string $leadId,
        public ?string $name,
        public ?string $phone,
        public string $origin,
        public ?string $landingUrl,
        public DateTimeImmutable $createdAt,
    ) {}
}
