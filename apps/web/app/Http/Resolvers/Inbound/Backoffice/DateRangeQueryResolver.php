<?php

declare(strict_types=1);

namespace App\Http\Resolvers\Inbound\Backoffice;

use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Inbound\Domain\Shared\DateRange;
use InvalidArgumentException;

final class DateRangeQueryResolver
{
    private const BUSINESS_TIMEZONE = 'Europe/Kyiv';

    public function resolve(
        Request $request,
        string $presetKey = 'preset',
        string $fromKey = 'from',
        string $toKey = 'to',
    ): ?DateRange {
        $preset = $this->resolveString($request, $presetKey) ?? 'all';

        return match ($preset) {
            'all' => null,
            'last_7_days' => $this->resolveRecentDaysRange(7),
            'last_30_days' => $this->resolveRecentDaysRange(30),
            'current_month' => $this->resolveCurrentMonthRange(),
            'previous_month' => $this->resolvePreviousMonthRange(),
            'custom' => $this->resolveCustomRange(
                $this->resolveString($request, $fromKey),
                $this->resolveString($request, $toKey),
            ),
            default => null,
        };
    }

    private function resolveRecentDaysRange(int $days): DateRange
    {
        $today = CarbonImmutable::now(self::BUSINESS_TIMEZONE)->startOfDay();

        return new DateRange(
            fromInclusive: $today->subDays($days - 1)->toDateTimeImmutable(),
            toExclusive: $today->addDay()->toDateTimeImmutable(),
        );
    }

    private function resolveCurrentMonthRange(): DateRange
    {
        $startOfMonth = CarbonImmutable::now(self::BUSINESS_TIMEZONE)->startOfMonth()->startOfDay();

        return new DateRange(
            fromInclusive: $startOfMonth->toDateTimeImmutable(),
            toExclusive: $startOfMonth->addMonth()->toDateTimeImmutable(),
        );
    }

    private function resolvePreviousMonthRange(): DateRange
    {
        $startOfCurrentMonth = CarbonImmutable::now(self::BUSINESS_TIMEZONE)->startOfMonth()->startOfDay();
        $startOfPreviousMonth = $startOfCurrentMonth->subMonth();

        return new DateRange(
            fromInclusive: $startOfPreviousMonth->toDateTimeImmutable(),
            toExclusive: $startOfCurrentMonth->toDateTimeImmutable(),
        );
    }

    private function resolveCustomRange(?string $from, ?string $to): ?DateRange
    {
        $fromInclusive = $this->parseBoundaryStart($from);
        $toExclusive = $this->parseBoundaryEndExclusive($to);

        if ($fromInclusive === null && $toExclusive === null) {
            return null;
        }

        try {
            return new DateRange(
                fromInclusive: $fromInclusive,
                toExclusive: $toExclusive,
            );
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    private function parseBoundaryStart(?string $value): ?DateTimeImmutable
    {
        $date = $this->parseDate($value);

        return $date?->startOfDay()->toDateTimeImmutable();
    }

    private function parseBoundaryEndExclusive(?string $value): ?DateTimeImmutable
    {
        $date = $this->parseDate($value);

        return $date?->startOfDay()->addDay()->toDateTimeImmutable();
    }

    private function parseDate(?string $value): ?CarbonImmutable
    {
        if ($value === null) {
            return null;
        }

        $date = CarbonImmutable::createFromFormat('Y-m-d', $value, self::BUSINESS_TIMEZONE);

        if ($date !== false && $date->format('Y-m-d') === $value) {
            return $date;
        }

        return null;
    }

    private function resolveString(Request $request, string $key): ?string
    {
        $value = $request->query($key);

        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
