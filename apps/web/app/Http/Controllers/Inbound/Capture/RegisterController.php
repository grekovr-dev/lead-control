<?php

namespace App\Http\Controllers\Inbound\Capture;

use App\Http\Controllers\Controller;
use App\Http\Cookies\Inbound\Capture\AttributionCookieStore;
use App\Http\Cookies\Inbound\Capture\ReferrerCookieStore;
use App\Http\Requests\Inbound\Capture\RegisterTouchRequest;
use App\Http\Resolvers\Inbound\Capture\VisitorIdCookieResolver;
use DateTimeImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
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
        private VisitorIdCookieResolver $visitorIdCookieResolver,
        private AttributionCookieStore $attributionCookieStore,
        private ReferrerCookieStore $referrerCookieStore,
    ) {
    }

    public function click(Request $request, RegisterClickAction $action): JsonResponse
    {
        $visitorId = $this->visitorIdCookieResolver->resolve($request);
        $command = new RegisterClickCommand(
            new ClickId((string) Str::uuid()),
            new VisitId((string) Str::uuid()),
            $visitorId,
            $this->attributionCookieStore->resolve($request),
            url('/'),
            $this->referrerCookieStore->resolve($request),
            new DateTimeImmutable(),
        );

        $visit = $action($command);

        return response()->json([
            'ok' => true,
            'data' => [
                'clickId' => $command->clickId->value(),
                'visitId' => $visit->id()->value(),
                'visitorId' => $visitorId->value(),
            ],
        ]);
    }

    public function touch(RegisterTouchRequest $request, RegisterTouchAction $action): JsonResponse
    {
        $visitorId = $this->visitorIdCookieResolver->resolve($request);
        $command = new RegisterTouchCommand(
            new TouchId((string) Str::uuid()),
            new VisitId((string) Str::uuid()),
            $visitorId,
            TouchType::from((string) $request->validated('type')),
            $this->attributionCookieStore->resolve($request),
            new DateTimeImmutable(),
        );

        $touch = $action($command);

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
}
