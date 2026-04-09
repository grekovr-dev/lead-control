<?php

declare(strict_types=1);

namespace Inbound\Application\Queries\Notifications\GetManagerLeadNotification;

interface ManagerLeadNotificationReadModel
{
    /**
     * @throws ManagerLeadNotificationNotFoundException
     */
    public function __invoke(GetManagerLeadNotificationQuery $query): ManagerLeadNotificationView;
}
