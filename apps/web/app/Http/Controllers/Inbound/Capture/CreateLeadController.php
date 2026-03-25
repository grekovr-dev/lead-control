<?php

namespace App\Http\Controllers\Inbound\Capture;

use App\Http\Controllers\Controller;
use App\Http\Cookies\Inbound\Capture\AttributionCookieStore;
use App\Http\Requests\Inbound\Capture\CreateLeadFromFormRequest;
use App\Http\Resolvers\Inbound\Capture\VisitorIdCookieResolver;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inbound\Application\Actions\Capture\CreateLeadFromForm\ActiveVisitNotFoundException as FormActiveVisitNotFoundException;
use Inbound\Application\Actions\Capture\CreateLeadFromForm\CreateLeadFromFormAction;
use Inbound\Application\Actions\Capture\CreateLeadFromForm\CreateLeadFromFormCommand;
use Inbound\Application\Actions\Capture\CreateLeadFromPhoneClick\ActiveVisitNotFoundException as PhoneClickActiveVisitNotFoundException;
use Inbound\Application\Actions\Capture\CreateLeadFromPhoneClick\CreateLeadFromPhoneClickAction;
use Inbound\Application\Actions\Capture\CreateLeadFromPhoneClick\CreateLeadFromPhoneClickCommand;
use Inbound\Domain\Lead\Lead;
use Inbound\Domain\Lead\LeadId;

class CreateLeadController extends Controller
{
    public function __construct(
        private VisitorIdCookieResolver $visitorIdCookieResolver,
        private AttributionCookieStore $attributionCookieStore,
    ) {
    }

    public function form(CreateLeadFromFormRequest $request, CreateLeadFromFormAction $action): JsonResponse
    {
        $visitorId = $this->visitorIdCookieResolver->resolve($request);
        $command = new CreateLeadFromFormCommand(
            new LeadId((string) Str::uuid()),
            $visitorId,
            $this->resolveString($request, 'name'),
            (string) $request->validated('phone'),
            $this->attributionCookieStore->resolve($request),
            new DateTimeImmutable(),
        );

        try {
            $lead = $action($command);
        } catch (FormActiveVisitNotFoundException $exception) {
            return $this->activeVisitNotFoundResponse($exception->getMessage());
        }

        return $this->createdResponse($lead);
    }

    public function phoneClick(Request $request, CreateLeadFromPhoneClickAction $action): JsonResponse
    {
        $visitorId = $this->visitorIdCookieResolver->resolve($request);
        $command = new CreateLeadFromPhoneClickCommand(
            new LeadId((string) Str::uuid()),
            $visitorId,
            $this->attributionCookieStore->resolve($request),
            new DateTimeImmutable(),
        );

        try {
            $lead = $action($command);
        } catch (PhoneClickActiveVisitNotFoundException $exception) {
            return $this->activeVisitNotFoundResponse($exception->getMessage());
        }

        return $this->createdResponse($lead);
    }

    private function createdResponse(Lead $lead): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => [
                'leadId' => $lead->id()->value(),
                'visitId' => $lead->visitId()->value(),
                'visitorId' => $lead->visitorId()->value(),
                'origin' => $lead->origin(),
            ],
        ], 201);
    }

    private function activeVisitNotFoundResponse(string $message): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'code' => 'active_visit_not_found',
            'message' => $message,
        ], 409);
    }

    private function resolveString(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        return is_string($value) ? $value : null;
    }
}
