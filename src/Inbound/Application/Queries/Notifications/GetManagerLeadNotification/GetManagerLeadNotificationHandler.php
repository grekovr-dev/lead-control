<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Notifications\GetManagerLeadNotification;

final readonly class GetManagerLeadNotificationHandler
{
    public function __construct(
        private ManagerLeadNotificationReadModel $readModel,
    ) {}

    /**
     * @throws ManagerLeadNotificationNotFoundException
     */
    public function __invoke(GetManagerLeadNotificationQuery $query): ManagerLeadNotificationView
    {
        return ($this->readModel)($query);
    }
}
