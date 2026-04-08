<?php

declare(strict_types=1);

namespace App\Jobs\Inbound\Notifications;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class SendManagerLeadCreatedTelegramJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $leadId,
    ) {}

    public function handle(): void {}
}
