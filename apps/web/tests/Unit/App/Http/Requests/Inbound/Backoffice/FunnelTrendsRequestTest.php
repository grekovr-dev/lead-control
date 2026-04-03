<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http\Requests\Inbound\Backoffice;

use App\Http\Requests\Inbound\Backoffice\FunnelTrendsRequest;
use App\Http\Resolvers\Inbound\Backoffice\DateRangeQueryResolver;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Tests\TestCase;

final class FunnelTrendsRequestTest extends TestCase
{
    public function test_it_defaults_to_all_and_builds_an_unbounded_query(): void
    {
        $request = $this->makeRequest([]);

        $request->validateResolved();
        $query = $request->toQuery(new DateRangeQueryResolver);

        $this->assertSame([
            'preset' => 'all',
            'from' => null,
            'to' => null,
        ], $request->filters());
        $this->assertArrayHasKey('last_30_days', $request->presetOptions());
        $this->assertNull($query->dateFrom);
        $this->assertNull($query->dateTo);
    }

    public function test_it_builds_query_bounds_from_custom_period(): void
    {
        $request = $this->makeRequest([
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ]);

        $request->validateResolved();
        $query = $request->toQuery(new DateRangeQueryResolver);

        $this->assertSame('2026-03-01 00:00:00', $query->dateFrom?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-03-31 00:00:00', $query->dateTo?->format('Y-m-d H:i:s'));
    }

    public function test_it_allows_a_custom_period_with_only_a_single_boundary(): void
    {
        $request = $this->makeRequest([
            'preset' => 'custom',
            'from' => '2026-03-01',
        ]);

        $request->validateResolved();
        $query = $request->toQuery(new DateRangeQueryResolver);

        $this->assertSame([
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => null,
        ], $request->filters());
        $this->assertSame('2026-03-01 00:00:00', $query->dateFrom?->format('Y-m-d H:i:s'));
        $this->assertNull($query->dateTo);
    }

    public function test_it_rejects_reversed_custom_ranges(): void
    {
        $request = $this->makeRequest([
            'preset' => 'custom',
            'from' => '2026-04-01',
            'to' => '2026-03-01',
        ]);

        try {
            $request->validateResolved();
            $this->fail('Expected validation to fail for a reversed custom date range.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('range', $exception->errors());
            $this->assertSame('Дата до не може бути раніше за дату від.', $exception->errors()['range'][0]);
        }
    }

    private function makeRequest(array $data): FunnelTrendsRequest
    {
        $baseRequest = Request::create('/admin/reports/funnel-trends', 'GET', $data);
        $request = FunnelTrendsRequest::createFromBase($baseRequest);

        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));
        $request->setUserResolver(static fn () => null);
        $request->setRouteResolver(fn (): Route => new Route('GET', '/admin/reports/funnel-trends', []));

        $this->app->instance('request', $request);
        $this->app->instance('routes', new RouteCollection);
        $this->app->make(ValidationFactory::class);

        return $request;
    }
}
