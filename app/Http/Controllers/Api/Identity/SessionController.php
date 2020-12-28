<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Identity\Sessions\IndexSessionsRequest;
use App\Services\Forus\Session\Models\Session;
use App\Services\Forus\Session\Resources\SessionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class SessionController
 * @package App\Http\Controllers\Api\Identity
 */
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
        $this->authorize('viewAny', Session::class);

        return SessionResource::collection(Session::where([
            'identity_address' => $request->auth_address()
        ])->orderByDesc('last_activity_at')->paginate(20));
    }

    /**
     * Display the specified resource.
     *
     * @param Session $session
     * @return SessionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Session $session): SessionResource
    {
        $this->authorize('show', $session);

        return new SessionResource($session);
    }

    /**
     * Display the specified resource.
     *
     * @param Session $session
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function terminate(Session $session): JsonResponse
    {
        $this->authorize('terminate', $session);

        $session->terminate();

        return response()->json([]);
    }

    /**
     * Display the specified resource.
     *
     * @return JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Exception
     */
    public function terminateAll(): JsonResponse
    {
        $this->authorize('terminateAll', Session::class);

        Session::terminateAll(auth_address());

        return response()->json([]);
    }
}
