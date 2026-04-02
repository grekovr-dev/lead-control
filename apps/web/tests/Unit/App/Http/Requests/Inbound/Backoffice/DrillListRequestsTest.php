<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http\Requests\Inbound\Backoffice;

use App\Http\Requests\Inbound\Backoffice\ListClicksRequest;
use App\Http\Requests\Inbound\Backoffice\ListTouchesRequest;
use App\Http\Requests\Inbound\Backoffice\ListVisitsRequest;
use App\Http\Resolvers\Inbound\Backoffice\DateRangeQueryResolver;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Request;
use Tests\TestCase;

final class DrillListRequestsTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_normalizes_clicks_query_params_and_exposes_read_only_drill_context(): void
    {
        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-04-01 10:15:00', 'Europe/Kyiv'));

        $resolver = new DateRangeQueryResolver;
        $request = $this->makeRequest(
            ListClicksRequest::class,
            '/admin/clicks',
            [
                'visitorId' => ' visitor-123 ',
                'attributionSource' => ' google ',
                'attributionMedium' => ' cpc ',
                'attributionCampaign' => ' spring-sale ',
                'preset' => 'last_7_days',
                'page' => '0',
                'perPage' => '50',
            ],
        );

        $request->validateResolved();

        $this->assertSame([
            'visitorId' => 'visitor-123',
            'attributionSource' => 'google',
            'attributionSourceMissing' => false,
            'attributionMedium' => 'cpc',
            'attributionMediumMissing' => false,
            'attributionCampaign' => 'spring-sale',
            'attributionCampaignMissing' => false,
            'preset' => 'last_7_days',
            'from' => null,
            'to' => null,
            'page' => 1,
            'perPage' => 50,
        ], $request->filters($resolver));
        $this->assertSame([
            ['label' => 'ID відвідувача', 'value' => 'visitor-123'],
            ['label' => 'Джерело атрибуції', 'value' => 'google'],
            ['label' => 'Канал атрибуції', 'value' => 'cpc'],
            ['label' => 'Кампанія', 'value' => 'spring-sale'],
            ['label' => 'Період кліків', 'value' => '26.03.2026 - 01.04.2026'],
        ], $request->drillContextItems($resolver));
        $this->assertSame([
            'visitorId' => 'visitor-123',
            'attributionSource' => 'google',
            'attributionMedium' => 'cpc',
            'attributionCampaign' => 'spring-sale',
            'preset' => 'last_7_days',
            'perPage' => 50,
        ], $request->paginationQuery($resolver));
    }

    public function test_it_normalizes_visits_query_params_and_exposes_read_only_drill_context(): void
    {
        $resolver = new DateRangeQueryResolver;
        $request = $this->makeRequest(
            ListVisitsRequest::class,
            '/admin/visits',
            [
                'visitorId' => ' visitor-123 ',
                'firstAttributionSource' => ' google ',
                'firstAttributionMedium' => ' cpc ',
                'firstAttributionCampaign' => ' spring-sale ',
                'lastAttributionSource' => ' google ',
                'lastAttributionMedium' => ' organic ',
                'from' => '2026-03-01',
                'to' => '2026-03-31',
                'preset' => 'custom',
                'page' => '2',
                'perPage' => '100',
            ],
        );

        $request->validateResolved();

        $this->assertSame([
            'visitorId' => 'visitor-123',
            'firstAttributionSource' => 'google',
            'firstAttributionSourceMissing' => false,
            'firstAttributionMedium' => 'cpc',
            'firstAttributionMediumMissing' => false,
            'firstAttributionCampaign' => 'spring-sale',
            'firstAttributionCampaignMissing' => false,
            'lastAttributionSource' => 'google',
            'lastAttributionMedium' => 'organic',
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
            'page' => 2,
            'perPage' => 100,
        ], $request->filters($resolver));
        $this->assertSame([
            ['label' => 'ID відвідувача', 'value' => 'visitor-123'],
            ['label' => 'Перше джерело', 'value' => 'google'],
            ['label' => 'Перший канал', 'value' => 'cpc'],
            ['label' => 'Перша кампанія', 'value' => 'spring-sale'],
            ['label' => 'Останнє джерело', 'value' => 'google'],
            ['label' => 'Останній канал', 'value' => 'organic'],
            ['label' => 'Період візитів', 'value' => '01.03.2026 - 31.03.2026'],
        ], $request->drillContextItems($resolver));
        $this->assertSame([
            'visitorId' => 'visitor-123',
            'firstAttributionSource' => 'google',
            'firstAttributionMedium' => 'cpc',
            'firstAttributionCampaign' => 'spring-sale',
            'lastAttributionSource' => 'google',
            'lastAttributionMedium' => 'organic',
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
            'perPage' => 100,
        ], $request->paginationQuery($resolver));
    }

    public function test_it_preserves_missing_bucket_dimensions_for_clicks_and_visits(): void
    {
        $resolver = new DateRangeQueryResolver;

        $clicksRequest = $this->makeRequest(
            ListClicksRequest::class,
            '/admin/clicks',
            [
                'attributionSource' => 'google',
                'attributionMedium' => 'cpc',
                'attributionCampaignMissing' => '1',
                'preset' => 'custom',
                'from' => '2026-03-01',
                'to' => '2026-03-31',
            ],
        );

        $clicksRequest->validateResolved();

        $this->assertSame([
            'visitorId' => null,
            'attributionSource' => 'google',
            'attributionSourceMissing' => false,
            'attributionMedium' => 'cpc',
            'attributionMediumMissing' => false,
            'attributionCampaign' => null,
            'attributionCampaignMissing' => true,
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
            'page' => 1,
            'perPage' => 20,
        ], $clicksRequest->filters($resolver));
        $this->assertSame([
            ['label' => 'Джерело атрибуції', 'value' => 'google'],
            ['label' => 'Канал атрибуції', 'value' => 'cpc'],
            ['label' => 'Кампанія', 'value' => 'Без кампанії'],
            ['label' => 'Період кліків', 'value' => '01.03.2026 - 31.03.2026'],
        ], $clicksRequest->drillContextItems($resolver));
        $this->assertSame([
            'attributionSource' => 'google',
            'attributionMedium' => 'cpc',
            'attributionCampaignMissing' => '1',
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ], $clicksRequest->paginationQuery($resolver));

        $visitsRequest = $this->makeRequest(
            ListVisitsRequest::class,
            '/admin/visits',
            [
                'firstAttributionSource' => 'google',
                'firstAttributionMedium' => 'cpc',
                'firstAttributionCampaignMissing' => '1',
                'preset' => 'custom',
                'from' => '2026-03-01',
                'to' => '2026-03-31',
            ],
        );

        $visitsRequest->validateResolved();

        $this->assertSame([
            'visitorId' => null,
            'firstAttributionSource' => 'google',
            'firstAttributionSourceMissing' => false,
            'firstAttributionMedium' => 'cpc',
            'firstAttributionMediumMissing' => false,
            'firstAttributionCampaign' => null,
            'firstAttributionCampaignMissing' => true,
            'lastAttributionSource' => null,
            'lastAttributionMedium' => null,
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
            'page' => 1,
            'perPage' => 20,
        ], $visitsRequest->filters($resolver));
        $this->assertSame([
            ['label' => 'Перше джерело', 'value' => 'google'],
            ['label' => 'Перший канал', 'value' => 'cpc'],
            ['label' => 'Перша кампанія', 'value' => 'Без кампанії'],
            ['label' => 'Період візитів', 'value' => '01.03.2026 - 31.03.2026'],
        ], $visitsRequest->drillContextItems($resolver));
        $this->assertSame([
            'firstAttributionSource' => 'google',
            'firstAttributionMedium' => 'cpc',
            'firstAttributionCampaignMissing' => '1',
            'preset' => 'custom',
            'from' => '2026-03-01',
            'to' => '2026-03-31',
        ], $visitsRequest->paginationQuery($resolver));
    }

    public function test_it_normalizes_touches_query_params_and_exposes_read_only_drill_context(): void
    {
        $request = $this->makeRequest(
            ListTouchesRequest::class,
            '/admin/touches',
            [
                'visitId' => ' visit-123 ',
                'visitorId' => ' visitor-123 ',
                'type' => 'messenger_click',
                'page' => '3',
                'perPage' => '999',
            ],
        );

        $request->validateResolved();

        $this->assertSame([
            'visitId' => 'visit-123',
            'visitorId' => 'visitor-123',
            'type' => 'messenger_click',
            'page' => 3,
            'perPage' => 20,
        ], $request->filters());
        $this->assertSame([
            ['label' => 'ID візиту', 'value' => 'visit-123'],
            ['label' => 'ID відвідувача', 'value' => 'visitor-123'],
            ['label' => 'Тип дотику', 'value' => 'Клік по месенджеру'],
        ], $request->drillContextItems());
        $this->assertSame([
            'visitId' => 'visit-123',
            'visitorId' => 'visitor-123',
            'type' => 'messenger_click',
        ], $request->paginationQuery());
    }

    /**
     * @template T of \Illuminate\Foundation\Http\FormRequest
     *
     * @param  class-string<T>  $requestClass
     * @return T
     */
    private function makeRequest(string $requestClass, string $uri, array $data)
    {
        $baseRequest = Request::create($uri, 'GET', $data);
        $request = $requestClass::createFromBase($baseRequest);

        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));
        $request->setUserResolver(static fn () => null);
        $request->setRouteResolver(fn (): Route => new Route('GET', $uri, []));

        $this->app->instance('request', $request);
        $this->app->instance('routes', new RouteCollection);
        $this->app->make(ValidationFactory::class);

        return $request;
    }
}
