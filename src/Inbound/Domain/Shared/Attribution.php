<?php

declare(strict_types=1);

namespace Inbound\Domain\Shared;

final readonly class Attribution
{
    private ?string $source;

    private ?string $medium;

    private ?string $campaign;

    private ?string $content;

    private ?string $term;

    private ?string $gclid;

    private ?string $fbclid;

    private ?string $msclkid;

    private ?string $referrer;

    public function __construct(
        ?string $source,
        ?string $medium,
        ?string $campaign,
        ?string $content,
        ?string $term,
        ?string $gclid,
        ?string $fbclid,
        ?string $msclkid,
        ?string $referrer = null,
    ) {
        $this->source = self::normalize($source);
        $this->medium = self::normalize($medium);
        $this->campaign = self::normalize($campaign);
        $this->content = self::normalize($content);
        $this->term = self::normalize($term);
        $this->gclid = self::normalize($gclid);
        $this->fbclid = self::normalize($fbclid);
        $this->msclkid = self::normalize($msclkid);
        $this->referrer = self::normalize($referrer);
    }

    public static function empty(): self
    {
        return new self(null, null, null, null, null, null, null, null, null);
    }

    public static function direct(): self
    {
        return new self('direct', 'none', null, null, null, null, null, null, null);
    }

    public function source(): ?string
    {
        return $this->source;
    }

    public function medium(): ?string
    {
        return $this->medium;
    }

    public function campaign(): ?string
    {
        return $this->campaign;
    }

    public function content(): ?string
    {
        return $this->content;
    }

    public function term(): ?string
    {
        return $this->term;
    }

    public function gclid(): ?string
    {
        return $this->gclid;
    }

    public function fbclid(): ?string
    {
        return $this->fbclid;
    }

    public function msclkid(): ?string
    {
        return $this->msclkid;
    }

    public function referrer(): ?string
    {
        return $this->referrer;
    }

    public function isEmpty(): bool
    {
        return $this->source === null
            && $this->medium === null
            && $this->campaign === null
            && $this->content === null
            && $this->term === null
            && $this->gclid === null
            && $this->fbclid === null
            && $this->msclkid === null
            && $this->referrer === null;
    }

    /**
     * @return array{
     *     source: ?string,
     *     medium: ?string,
     *     campaign: ?string,
     *     content: ?string,
     *     term: ?string,
     *     gclid: ?string,
     *     fbclid: ?string,
     *     msclkid: ?string,
     *     referrer: ?string
     * }
     */
    public function toArray(): array
    {
        return [
            'source' => $this->source,
            'medium' => $this->medium,
            'campaign' => $this->campaign,
            'content' => $this->content,
            'term' => $this->term,
            'gclid' => $this->gclid,
            'fbclid' => $this->fbclid,
            'msclkid' => $this->msclkid,
            'referrer' => $this->referrer,
        ];
    }

    public function equals(self $other): bool
    {
        return $this->source === $other->source
            && $this->medium === $other->medium
            && $this->campaign === $other->campaign
            && $this->content === $other->content
            && $this->term === $other->term
            && $this->gclid === $other->gclid
            && $this->fbclid === $other->fbclid
            && $this->msclkid === $other->msclkid
            && $this->referrer === $other->referrer;
    }

    private static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
