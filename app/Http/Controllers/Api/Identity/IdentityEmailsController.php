<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Identity\Emails\IndexIdentityEmailRequest;
use App\Http\Requests\Api\Identity\Emails\ResendIdentityEmailRequest;
use App\Http\Requests\Api\Identity\Emails\StoreIdentityEmailRequest;
use App\Http\Resources\IdentityEmailResource;
use App\Services\Forus\Identity\Models\IdentityEmail;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

/**
 * Class IdentityEmailsController
 * @package App\Http\Controllers\Api\Identity
 */
class IdentityEmailsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param IndexIdentityEmailRequest $request
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function index(IndexIdentityEmailRequest $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', IdentityEmail::class);

        $query = IdentityEmail::where([
            'identity_address' => $request->auth_address()
        ])->orderByDesc('primary')->orderBy('email');

        return IdentityEmailResource::queryCollection($query, (clone $query)->count());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param StoreIdentityEmailRequest $request
     * @return IdentityEmailResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function store(StoreIdentityEmailRequest $request): IdentityEmailResource
    {
        $this->authorize('create', IdentityEmail::class);

        $identityEmail = $request->identity_repo()->addIdentityEmail(
            $request->auth_address(),
            $request->input('email')
        );

        return new IdentityEmailResource($identityEmail->sendVerificationEmail());
    }

    /**
     * Display the specified resource.
     *
     * @param IdentityEmail $identityEmail
     * @return IdentityEmailResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function show(IdentityEmail $identityEmail): IdentityEmailResource
    {
        $this->authorize('view', $identityEmail);

        return new IdentityEmailResource($identityEmail);
    }

    /**
     * Resend email confirmation link
     *
     * @param ResendIdentityEmailRequest $request
     * @param IdentityEmail $identityEmail
     * @return IdentityEmailResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function resend(
        /** @noinspection PhpUnusedParameterInspection */
        ResendIdentityEmailRequest $request,
        IdentityEmail $identityEmail
    ): IdentityEmailResource {
        $this->authorize('resend', $identityEmail);

        return new IdentityEmailResource($identityEmail->sendVerificationEmail());
    }

    /**
     * @param IdentityEmail $identityEmail
     * @return IdentityEmailResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function primary(IdentityEmail $identityEmail): IdentityEmailResource
    {
        $this->authorize('makePrimary', $identityEmail);

        return new IdentityEmailResource($identityEmail->setPrimary());
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param IdentityEmail $identityEmail
     * @return \Illuminate\Http\JsonResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException|\Exception
     */
    public function destroy(IdentityEmail $identityEmail): JsonResponse
    {
        $this->authorize('delete', $identityEmail);

        $identityEmail->delete();

        return response()->json([]);
    }

    /**
     * @param IdentityEmail $identityEmail
     * @return \Illuminate\Contracts\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function emailVerificationToken(IdentityEmail $identityEmail): View
    {
        $this->authorize('verifyToken', $identityEmail);

        $identityEmail->setVerified();

        return view('messages.email-verified');
    }
}
