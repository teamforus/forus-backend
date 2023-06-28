<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Identity\Sessions\IndexSessionsRequest;
use App\Http\Requests\BaseFormRequest;
use App\Services\Forus\Session\Models\Session;
use App\Services\Forus\Session\Resources\SessionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SessionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexSessionsRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(IndexSessionsRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', [Session::class, $request->identityProxy2FAConfirmed()]);

        $query = Session::query()
            ->where('identity_address', $request->auth_address())
            ->whereHas('identity_proxy')
            ->orderByDesc('last_activity_at');

        return SessionResource::queryCollection($query);
    }

    /**
     * Display the specified resource.
     *
     * @param BaseFormRequest $request
     * @param Session $session
     * @return SessionResource
     */
    public function show(BaseFormRequest $request, Session $session): SessionResource
    {
        $this->authorize('show', [$session, $request->identityProxy2FAConfirmed()]);

        return new SessionResource($session);
    }

    /**
     * Display the specified resource.
     *
     * @param BaseFormRequest $request
     * @param Session $session
     * @return JsonResponse
     * @throws \Throwable
     */
    public function terminate(BaseFormRequest $request, Session $session): JsonResponse
    {
        $this->authorize('terminate', [$session, $request->identityProxy2FAConfirmed()]);

        $session->terminate(false);

        return new JsonResponse();
    }

    /**
     * Display the specified resource.
     *
     * @param BaseFormRequest $request
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Throwable
     * @noinspection PhpUnused
     */
    public function terminateAll(BaseFormRequest $request): JsonResponse
    {
        $this->authorize('terminateAll', [Session::class, $request->identityProxy2FAConfirmed()]);

        foreach ($request->identity()->proxies as $proxy) {
            $proxy->deactivateBySession(false);
        }

        return new JsonResponse();
    }
}
