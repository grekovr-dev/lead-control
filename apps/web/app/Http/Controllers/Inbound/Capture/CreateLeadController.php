<?php

namespace App\Http\Controllers\Inbound\Capture;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inbound\Capture\CreateLeadFromFormRequest;
use App\Http\Resolvers\Inbound\Capture\VisitorIdResolver;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inbound\Application\Actions\Capture\CreateLeadFromForm\CreateLeadFromFormAction;
use Inbound\Application\Actions\Capture\CreateLeadFromForm\CreateLeadFromFormCommand;
use Inbound\Application\Actions\Capture\CreateLeadFromForm\CurrentVisitNotFoundException as FormCurrentVisitNotFoundException;
use Inbound\Application\Actions\Capture\PhoneClick\CapturePhoneClickAction;
use Inbound\Application\Actions\Capture\PhoneClick\CapturePhoneClickCommand;
use Inbound\Application\Actions\Capture\PhoneClick\CurrentVisitNotFoundException as PhoneClickCurrentVisitNotFoundException;
use Inbound\Domain\Lead\Lead;
use Inbound\Domain\Lead\LeadId;
use Inbound\Domain\Touch\Touch;
use Inbound\Domain\Touch\TouchId;

class CreateLeadController extends Controller
{
    public function __construct(
        private VisitorIdResolver $visitorIdResolver,
    ) {}

    public function form(CreateLeadFromFormRequest $request, CreateLeadFromFormAction $action): JsonResponse
    {
        $visitorId = $this->visitorIdResolver->resolve($request);

        if ($visitorId === null) {
            return $this->visitorIdNotFoundResponse();
        }

        $command = new CreateLeadFromFormCommand(
            new LeadId((string) Str::uuid()),
            $visitorId,
            $this->resolveString($request, 'name'),
            (string) $request->validated('phone'),
            new DateTimeImmutable,
        );

        try {
            $lead = $action($command);
        } catch (FormCurrentVisitNotFoundException $exception) {
            return $this->currentVisitNotFoundResponse($exception->getMessage());
        }

        return $this->createdResponse($lead);
    }

    public function phoneClick(Request $request, CapturePhoneClickAction $action): JsonResponse
    {
        $visitorId = $this->visitorIdResolver->resolve($request);

        if ($visitorId === null) {
            return $this->visitorIdNotFoundResponse();
        }

        $command = new CapturePhoneClickCommand(
            new LeadId((string) Str::uuid()),
            new TouchId((string) Str::uuid()),
            $visitorId,
            new DateTimeImmutable,
        );

        try {
            $result = $action($command);
        } catch (PhoneClickCurrentVisitNotFoundException $exception) {
            return $this->currentVisitNotFoundResponse('Cannot create lead from phone click without a current visit.');
        }

        if ($result instanceof Lead) {
            return $this->createdResponse($result);
        }

        return $this->touchResponse($result);
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

    private function touchResponse(Touch $touch): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'data' => [
                'touchId' => $touch->id()->value(),
                'visitId' => $touch->visitId()->value(),
                'visitorId' => $touch->visitorId()->value(),
                'type' => $touch->type()->value,
            ],
        ]);
    }

    private function currentVisitNotFoundResponse(string $message): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'code' => 'current_visit_not_found',
            'message' => $message,
        ], 409);
    }

    private function visitorIdNotFoundResponse(): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'code' => 'visitor_id_not_found',
            'message' => 'Visitor context is missing.',
        ], 409);
    }

    private function resolveString(Request $request, string $key): ?string
    {
        $value = $request->input($key);

        return is_string($value) ? $value : null;
    }
}
