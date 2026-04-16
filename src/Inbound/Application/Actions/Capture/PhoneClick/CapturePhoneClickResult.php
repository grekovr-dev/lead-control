<?php

declare(strict_types=1);

namespace Inbound\Application\Actions\Capture\PhoneClick;

final readonly class CapturePhoneClickResult
{
    public const TYPE_LEAD = 'lead';

    public const TYPE_TOUCH = 'touch';

    public function __construct(
        public string $visitorId,
        public string $visitId,
        public string $resultType,
        public string $resultId,
    ) {}
}
