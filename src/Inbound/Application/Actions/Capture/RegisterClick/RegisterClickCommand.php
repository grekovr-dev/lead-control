<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\RegisterClick;

use DateTimeImmutable;
use Inbound\Domain\Click\ClickId;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\VisitId;

final readonly class RegisterClickCommand
{
    public function __construct(
        public ClickId $clickId,
        public VisitId $visitId,
        public VisitorId $visitorId,
        public Attribution $attribution,
        public string $landingUrl,
        public ?string $referrer,
        public DateTimeImmutable $occurredAt,
    ) {
    }
}
