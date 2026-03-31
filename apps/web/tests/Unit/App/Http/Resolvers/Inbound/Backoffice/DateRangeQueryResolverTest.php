<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http\Resolvers\Inbound\Backoffice;

use App\Http\Resolvers\Inbound\Backoffice\DateRangeQueryResolver;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

final class DateRangeQueryResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_returns_null_for_the_all_preset(): void
    {
        $resolver = new DateRangeQueryResolver();
        $request = Request::create('/', 'GET', [
            'preset' => 'all',
        ]);

        $result = $resolver->resolve($request);

        $this->assertNull($result);
    }

    public function test_it_resolves_the_last_7_days_preset_in_business_timezone(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-03-30 10:15:00', 'Europe/Kyiv'));

        $resolver = new DateRangeQueryResolver();
        $request = Request::create('/', 'GET', [
            'preset' => 'last_7_days',
        ]);

        $result = $resolver->resolve($request);

        $this->assertNotNull($result);
        $this->assertSame('2026-03-24 00:00:00', $result->fromInclusive()?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-31 00:00:00', $result->toExclusive()?->format('Y-m-d H:i:s'));
    }

    public function test_it_resolves_a_custom_range_with_open_boundaries(): void
    {
        $resolver = new DateRangeQueryResolver();

        $fromOnly = $resolver->resolve(Request::create('/', 'GET', [
            'preset' => 'custom',
            'from' => '2026-03-01',
        ]));
        $toOnly = $resolver->resolve(Request::create('/', 'GET', [
            'preset' => 'custom',
            'to' => '2026-03-31',
        ]));

        $this->assertNotNull($fromOnly);
        $this->assertSame('2026-03-01 00:00:00', $fromOnly->fromInclusive()?->format('Y-m-d H:i:s'));
        $this->assertNull($fromOnly->toExclusive());

        $this->assertNotNull($toOnly);
        $this->assertNull($toOnly->fromInclusive());
        $this->assertSame('2026-04-01 00:00:00', $toOnly->toExclusive()?->format('Y-m-d H:i:s'));
    }
}
