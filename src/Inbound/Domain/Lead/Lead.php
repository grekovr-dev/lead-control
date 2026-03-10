<?php

namespace Inbound\Domain\Lead;

final class Lead
{
    public function __construct(
        public readonly ?int $id,
        public readonly ?string $name,
        public readonly string $phone,
        public readonly ?string $source,
        public readonly LeadStatus $status,
    ) {
    }
}
