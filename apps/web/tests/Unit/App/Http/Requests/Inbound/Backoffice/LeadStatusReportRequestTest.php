<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http\Requests\Inbound\Backoffice;

use App\Http\Requests\Inbound\Backoffice\LeadStatusReportRequest;
use App\Http\Resolvers\Inbound\Backoffice\DateRangeQueryResolver;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Tests\TestCase;

final class LeadStatusReportRequestTest extends TestCase
{
    public function test_it_defaults_to_all_and_exposes_filter_options(): void
    {
        $request = $this->makeRequest([]);

        $request->validateResolved();

        $this->assertSame([
            'preset' => 'all',
            'from' => null,
            'to' => null,
        ], $request->filters());
        $this->assertArrayHasKey('last_7_days', $request->presetOptions());
        $this->assertNull($request->resolveDateRange(new DateRangeQueryResolver()));
    }

    public function test_it_allows_a_custom_range_with_only_a_single_boundary(): void
    {
        $request = $this->makeRequest([
            'preset' => 'custom',
            'from' => '2026-03-01',
        ]);

        $request->validateResolved();
        $range = $request->resolveDateRange(new DateRangeQueryResolver());

        $this->assertSame('custom', $request->filters()['preset']);
        $this->assertNotNull($range);
        $this->assertSame('2026-03-01 00:00:00', $range->fromInclusive()?->format('Y-m-d H:i:s'));
        $this->assertNull($range->toExclusive());
    }

    public function test_it_rejects_custom_ranges_where_to_is_earlier_than_from(): void
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

    private function makeRequest(array $data): LeadStatusReportRequest
    {
        $baseRequest = Request::create('/admin/reports/lead-status', 'GET', $data);
        $request = LeadStatusReportRequest::createFromBase($baseRequest);

        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));
        $request->setUserResolver(static fn () => null);
        $request->setRouteResolver(function (): Route {
            return new Route('GET', '/admin/reports/lead-status', []);
        });

        $this->app->instance('request', $request);
        $this->app->instance('routes', new RouteCollection());
        $this->app->make(ValidationFactory::class);

        return $request;
    }
}
