<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\RegisterClick;

use DateTimeImmutable;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;

final readonly class RegisterClickCommand
{
    public function __construct(
        public VisitorId $visitorId,
        public Attribution $attribution,
        public string $landingUrl,
        public DateTimeImmutable $occurredAt,
    ) {}
}
