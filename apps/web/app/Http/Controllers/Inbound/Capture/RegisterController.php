<?php

namespace App\Http\Controllers\Inbound\Capture;

use App\Http\Controllers\Controller;
use App\Http\Cookies\Inbound\Capture\AttributionCookieStore;
use App\Http\Requests\Inbound\Capture\RegisterTouchRequest;
use App\Http\Resolvers\Inbound\Capture\VisitorIdResolver;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inbound\Application\Actions\Capture\ContinueCurrentVisit\CurrentVisitNotFoundException;
use Inbound\Application\Actions\Capture\RegisterClick\RegisterClickAction;
use Inbound\Application\Actions\Capture\RegisterClick\RegisterClickCommand;
use Inbound\Application\Actions\Capture\RegisterTouch\RegisterTouchAction;
use Inbound\Application\Actions\Capture\RegisterTouch\RegisterTouchCommand;
use Inbound\Domain\Click\ClickId;
use Inbound\Domain\Touch\TouchId;
use Inbound\Domain\Touch\TouchType;
use Inbound\Domain\Visit\VisitId;

class RegisterController extends Controller
{
    public function __construct(
        private VisitorIdResolver $visitorIdResolver,
        private AttributionCookieStore $attributionCookieStore,
    ) {}

    public function click(Request $request, RegisterClickAction $action): JsonResponse
    {
        $visitorId = $this->visitorIdResolver->resolve($request);

        if ($visitorId === null) {
            return $this->visitorIdNotFoundResponse();
        }

        $attribution = $this->attributionCookieStore->resolve($request);

        $command = new RegisterClickCommand(
            new ClickId((string) Str::uuid()),
            new VisitId((string) Str::uuid()),
            $visitorId,
            $attribution,
            url('/'),
            new DateTimeImmutable,
        );

        $visit = $action($command);

        $response = response()->json([
            'ok' => true,
            'data' => [
                'clickId' => $command->clickId->value(),
                'visitId' => $visit->id()->value(),
                'visitorId' => $visitorId->value(),
            ],
        ]);

        $response->headers->setCookie($this->attributionCookieStore->forget());

        return $response;
    }

    public function touch(RegisterTouchRequest $request, RegisterTouchAction $action): JsonResponse
    {
        $visitorId = $this->visitorIdResolver->resolve($request);

        if ($visitorId === null) {
            return $this->visitorIdNotFoundResponse();
        }

        $command = new RegisterTouchCommand(
            new TouchId((string) Str::uuid()),
            $visitorId,
            TouchType::from((string) $request->validated('type')),
            new DateTimeImmutable,
        );

        try {
            $touch = $action($command);
        } catch (CurrentVisitNotFoundException $exception) {
            return response()->json([
                'ok' => false,
                'code' => 'current_visit_not_found',
                'message' => $exception->getMessage(),
            ], 409);
        }

        return response()->json([
            'ok' => true,
            'data' => [
                'touchId' => $touch->id()->value(),
                'visitId' => $touch->visitId()->value(),
                'visitorId' => $visitorId->value(),
                'type' => $touch->type()->value,
            ],
        ]);
    }

    private function visitorIdNotFoundResponse(): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'code' => 'visitor_id_not_found',
            'message' => 'Visitor context is missing.',
        ], 409);
    }
}
