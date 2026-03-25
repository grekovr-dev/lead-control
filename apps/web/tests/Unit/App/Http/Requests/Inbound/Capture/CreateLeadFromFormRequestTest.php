<?php

declare(strict_types=1);

namespace Tests\Unit\App\Http\Requests\Inbound\Capture;

use App\Http\Requests\Inbound\Capture\CreateLeadFromFormRequest;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Routing\Redirector;
use Illuminate\Routing\Route;
use Illuminate\Routing\RouteCollection;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Tests\TestCase;

final class CreateLeadFromFormRequestTest extends TestCase
{
    public function test_it_normalizes_phone_before_validation(): void
    {
        $request = $this->makeRequest([
            'name' => 'John Doe',
            'phone' => ' +380 (50) 111-22-33 ',
        ]);

        $request->validateResolved();

        $this->assertSame('+380501112233', $request->validated('phone'));
    }

    public function test_it_rejects_phone_when_result_is_not_e164(): void
    {
        $request = $this->makeRequest([
            'name' => 'John Doe',
            'phone' => '380 (50) 111-22-33',
        ]);

        try {
            $request->validateResolved();
            $this->fail('Expected validation to fail for a non-E.164 phone number.');
        } catch (HttpResponseException $exception) {
            $response = $exception->getResponse();

            $this->assertSame(422, $response->getStatusCode());
            $payload = json_decode((string) $response->getContent(), true);

            $this->assertIsArray($payload);
            $this->assertSame(false, $payload['ok']);
            $this->assertSame('validation_error', $payload['code']);
            $this->assertSame('The given data was invalid.', $payload['message']);
            $this->assertSame('The phone field format is invalid.', $payload['errors']['phone'][0]);
        }
    }

    private function makeRequest(array $data): CreateLeadFromFormRequest
    {
        $baseRequest = Request::create('/capture/leads/form', 'POST', $data);
        $request = CreateLeadFromFormRequest::createFromBase($baseRequest);

        $request->setContainer($this->app);
        $request->setRedirector($this->app->make(Redirector::class));
        $request->setUserResolver(static fn () => null);
        $request->setRouteResolver(function (): Route {
            return new Route('POST', '/capture/leads/form', []);
        });

        $this->app->instance('request', $request);
        $this->app->instance('routes', new RouteCollection());
        $this->app->make(ValidationFactory::class);

        return $request;
    }
}
