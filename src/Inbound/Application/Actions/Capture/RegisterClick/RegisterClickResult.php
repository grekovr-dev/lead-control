<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\RegisterClick;

final readonly class RegisterClickResult
{
    public const TYPE_CLICK = 'click';

    public const TYPE_REVISIT = 'revisit';

    public function __construct(
        public string $visitorId,
        public string $visitId,
        public string $resultType,
        public string $resultId,
    ) {}
}
