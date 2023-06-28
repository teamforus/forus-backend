<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Identity\Emails\IndexIdentityEmailRequest;
use App\Http\Requests\Api\Identity\Emails\ResendIdentityEmailRequest;
use App\Http\Requests\Api\Identity\Emails\StoreIdentityEmailRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\IdentityEmailResource;
use App\Models\IdentityEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

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
        $this->authorize('viewAny', [
            IdentityEmail::class,
            $request->identityProxy2FAConfirmed(),
        ]);

        $query = $request->identity()->emails()->orderByDesc('primary')->orderBy('email');

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
        $this->authorize('create', [
            IdentityEmail::class,
            $request->identityProxy2FAConfirmed(),
        ]);

        $identityEmail = $request->identity()->addEmail($request->input('email'));
        $identityEmail->redirect()->create([
            'target' => $request->input('target'),
            'client_type' => $request->client_type(),
            'implementation_id' => $request->implementation()->id,
        ]);

        return new IdentityEmailResource($identityEmail->sendVerificationEmail());
    }

    /**
     * Display the specified resource.
     *
     * @param BaseFormRequest $request
     * @param IdentityEmail $identityEmail
     * @return IdentityEmailResource
     */
    public function show(
        BaseFormRequest $request,
        IdentityEmail $identityEmail,
    ): IdentityEmailResource {
        $this->authorize('view', [
            $identityEmail,
            $request->identityProxy2FAConfirmed(),
        ]);

        return new IdentityEmailResource($identityEmail);
    }

    /**
     * Resend email confirmation link
     *
     * @param ResendIdentityEmailRequest $request
     * @param IdentityEmail $identityEmail
     * @return IdentityEmailResource
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @noinspection PhpUnused
     */
    public function resend(
        ResendIdentityEmailRequest $request,
        IdentityEmail $identityEmail
    ): IdentityEmailResource {
        $this->authorize('resend', [
            $identityEmail,
            $request->identityProxy2FAConfirmed(),
        ]);

        return new IdentityEmailResource($identityEmail->sendVerificationEmail());
    }

    /**
     * @param BaseFormRequest $request
     * @param IdentityEmail $identityEmail
     * @return IdentityEmailResource
     */
    public function primary(
        BaseFormRequest $request,
        IdentityEmail $identityEmail,
    ): IdentityEmailResource {
        $this->authorize('makePrimary', [
            $identityEmail,
            $request->identityProxy2FAConfirmed(),
        ]);

        return new IdentityEmailResource($identityEmail->setPrimary());
    }

    /**
     * @param BaseFormRequest $request
     * @param IdentityEmail $identityEmail
     * @return JsonResponse
     */
    public function destroy(
        BaseFormRequest $request,
        IdentityEmail $identityEmail,
    ): JsonResponse {
        $this->authorize('delete', [
            $identityEmail,
            $request->identityProxy2FAConfirmed(),
        ]);

        $identityEmail->delete();

        return new JsonResponse([]);
    }

    /**
     * @param IdentityEmail $identityEmail
     * @return View|RedirectResponse
     * @noinspection PhpUnused
     */
    public function emailVerificationToken(IdentityEmail $identityEmail): View|RedirectResponse
    {
        $this->authorize('verifyToken', $identityEmail);

        $identityEmail->setVerified();

        if (!$identityEmail->redirect) {
            return view('messages.email-verified');
        }

        return redirect($identityEmail->redirect->targetUrl([
            'email_confirmed' => 1,
        ]));
    }
}
