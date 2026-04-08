<?php

declare(strict_types=1);

namespace Inbound\Domain\Lead;

use DateTimeImmutable;
use Inbound\Domain\Lead\Events\LeadCreated;
use Inbound\Domain\Shared\Attribution;
use Inbound\Domain\Shared\VisitorId;
use Inbound\Domain\Visit\VisitId;
use InvalidArgumentException;

final class Lead
{
    private const ORIGIN_FORM = 'form';

    private const ORIGIN_PHONE_CLICK = 'phone_click';

    private const ORIGIN_MESSENGER_CLICK = 'messenger_click';

    private LeadId $id;

    private VisitorId $visitorId;

    private VisitId $visitId;

    private ?string $name;

    private ?string $phone;

    private Attribution $visitAttribution;

    private Attribution $visitorAttribution;

    private LeadStatus $status;

    private string $origin;

    private ?string $landingUrl;

    private DateTimeImmutable $createdAt;

    /**
     * @var list<object>
     */
    private array $recordedEvents = [];

    public static function create(
        LeadId $id,
        VisitorId $visitorId,
        VisitId $visitId,
        ?string $name,
        ?string $phone,
        Attribution $visitAttribution,
        LeadStatus $status,
        string $origin,
        DateTimeImmutable $createdAt,
        Attribution $visitorAttribution,
        ?string $landingUrl = null,
    ): self {
        $lead = new self(
            $id,
            $visitorId,
            $visitId,
            $name,
            $phone,
            $visitAttribution,
            $status,
            $origin,
            $createdAt,
            $visitorAttribution,
            $landingUrl,
        );

        $lead->recordThat(new LeadCreated($lead->id(), $lead->createdAt()));

        return $lead;
    }

    public function __construct(
        LeadId $id,
        VisitorId $visitorId,
        VisitId $visitId,
        ?string $name,
        ?string $phone,
        Attribution $visitAttribution,
        LeadStatus $status,
        string $origin,
        DateTimeImmutable $createdAt,
        Attribution $visitorAttribution,
        ?string $landingUrl = null,
    ) {
        $origin = trim($origin);

        if ($origin === '') {
            throw new InvalidArgumentException('Lead origin cannot be empty.');
        }

        if (! in_array($origin, [
            self::ORIGIN_FORM,
            self::ORIGIN_PHONE_CLICK,
            self::ORIGIN_MESSENGER_CLICK,
        ], true)) {
            throw new InvalidArgumentException('Lead origin is invalid.');
        }

        $this->id = $id;
        $this->visitorId = $visitorId;
        $this->visitId = $visitId;
        $this->name = self::normalizeNullableString($name);
        $this->phone = self::normalizeNullableString($phone);
        $this->visitAttribution = $visitAttribution;
        $this->visitorAttribution = $visitorAttribution;
        $this->status = $status;
        $this->origin = $origin;
        $this->landingUrl = self::normalizeNullableString($landingUrl);
        $this->createdAt = $createdAt;
    }

    public function id(): LeadId
    {
        return $this->id;
    }

    public function visitorId(): VisitorId
    {
        return $this->visitorId;
    }

    public function visitId(): VisitId
    {
        return $this->visitId;
    }

    public function name(): ?string
    {
        return $this->name;
    }

    public function phone(): ?string
    {
        return $this->phone;
    }

    public function visitAttribution(): Attribution
    {
        return $this->visitAttribution;
    }

    public function visitorAttribution(): Attribution
    {
        return $this->visitorAttribution;
    }

    public function status(): LeadStatus
    {
        return $this->status;
    }

    public function origin(): string
    {
        return $this->origin;
    }

    public function landingUrl(): ?string
    {
        return $this->landingUrl;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function changeStatus(LeadStatus $status): void
    {
        $this->status = $status;
    }

    public function recordThat(object $event): void
    {
        $this->recordedEvents[] = $event;
    }

    /**
     * @return list<object>
     */
    public function releaseEvents(): array
    {
        $events = $this->recordedEvents;
        $this->recordedEvents = [];

        return $events;
    }

    private static function normalizeNullableString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
