<?php

namespace App\Http\Controllers;

use App\Http\Requests\DigID\CompleteDigIdRequest;
use App\Http\Requests\DigID\ResolveDigIdRequest;
use App\Http\Requests\DigID\StartDigIdRequest;
use App\Services\DigIdService\Controllers\BaseDigIdController;
use App\Services\DigIdService\Models\DigIdSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Throwable;

class DigIdController extends BaseDigIdController
{
    /**
     * @param StartDigIdRequest $request
     * @throws Throwable
     * @return JsonResponse
     */
    public function start(StartDigIdRequest $request): JsonResponse
    {
        $session = DigIdSession::createSession($request->sessionData());
        $authRequest = $session->startAuthSession();

        return $this->makeStartResponse($session, $authRequest);
    }

    /**
     * @param ResolveDigIdRequest $request
     * @param DigIdSession $session
     * @throws Throwable
     * @return RedirectResponse
     */
    public function resolve(ResolveDigIdRequest $request, DigIdSession $session): RedirectResponse
    {
        return $this->resolveSession($session, $request);
    }

    /**
     * @param CompleteDigIdRequest $request
     * @throws Throwable
     * @return JsonResponse
     */
    public function complete(CompleteDigIdRequest $request): JsonResponse
    {
        return $this->completeSession($request, 'digid');
    }
}
