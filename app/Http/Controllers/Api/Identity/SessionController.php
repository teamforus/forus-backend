<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Controllers\Controller;
use App\Services\Forus\Session\Models\Session;
use App\Services\Forus\Session\Resources\SessionResource;

class SessionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index()
    {
        $this->authorize('viewAny', Session::class);

        return SessionResource::collection(Session::where([
            'identity_address' => auth_address()
        ])->orderByDesc('last_activity_at')->paginate(20));
    }

    /**
     * Display the specified resource.
     *
     * @param Session $session
     * @return SessionResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(Session $session)
    {
        $this->authorize('show', $session);

        return new SessionResource($session);
    }

    /**
     * Display the specified resource.
     *
     * @param Session $session
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function terminate(Session $session)
    {
        $this->authorize('terminate', $session);

        $session->terminate();
    }

    /**
     * Display the specified resource.
     *
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function terminateAll()
    {
        $this->authorize('terminateAll', Session::class);

        Session::terminateAll(auth_address());
    }
}
