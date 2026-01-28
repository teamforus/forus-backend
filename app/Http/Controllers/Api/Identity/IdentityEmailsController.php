<?php

namespace App\Http\Controllers\Api\Identity;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Identity\Emails\IndexIdentityEmailRequest;
use App\Http\Requests\Api\Identity\Emails\ResendIdentityEmailRequest;
use App\Http\Requests\Api\Identity\Emails\StoreIdentityEmailRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\IdentityEmailResource;
use App\Models\IdentityEmail;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class IdentityEmailsController extends Controller
{
    /**
     * Handles the request to retrieve a list of identity emails.
     *
     * This method authorizes the user to view identity's emails and then retrieves them from the database,
     * ordered by primary status and email address. It returns a collection of resources representing the identity emails.
     *
     * @param IndexIdentityEmailRequest $request The incoming HTTP request.
     * @return AnonymousResourceCollection A collection of resources representing the identity emails.
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
     * Handles the request to store a new identity email.
     *
     * This method authorizes the user to create a new identity email, adds it to the identity,
     * creates a redirect for the email, and sends a verification email.
     * It returns a resource representation of the created identity email.
     *
     * @param StoreIdentityEmailRequest $request The incoming HTTP request containing the email details.
     * @return IdentityEmailResource A resource representing the created identity email.
     */
    public function store(StoreIdentityEmailRequest $request): IdentityEmailResource
    {
        $this->authorize('create', [
            IdentityEmail::class,
            $request->identityProxy2FAConfirmed(),
        ]);

        $identity = $request->identity();
        $identityEmail = $identity->addEmail($request->input('email'));

        $identityEmail->redirect()->create([
            'target' => $request->input('target'),
            'client_type' => $request->client_type(),
            'implementation_id' => $request->implementation()->id,
        ]);

        return IdentityEmailResource::create($identityEmail->sendVerificationEmail());
    }

    /**
     * Displays an identity email.
     *
     * This method authorizes the user to view an identity email and returns a resource representation of it.
     *
     * @param BaseFormRequest $request The incoming HTTP request.
     * @param IdentityEmail $identityEmail The identity email to be displayed.
     * @return IdentityEmailResource A resource representing the identity email.
     */
    public function show(BaseFormRequest $request, IdentityEmail $identityEmail): IdentityEmailResource
    {
        $this->authorize('view', [
            $identityEmail,
            $request->identityProxy2FAConfirmed(),
        ]);

        return IdentityEmailResource::create($identityEmail);
    }

    /**
     * Handles the request to resend a verification email for an identity email.
     *
     * This method authorizes the user to resend the verification email and then sends it.
     * It returns a resource representation of the updated identity email.
     *
     * @param ResendIdentityEmailRequest $request The incoming HTTP request.
     * @param IdentityEmail $identityEmail The identity email for which to resend the verification email.
     * @return IdentityEmailResource A resource representing the updated identity email.
     */
    public function resend(ResendIdentityEmailRequest $request, IdentityEmail $identityEmail): IdentityEmailResource
    {
        $this->authorize('resend', [
            $identityEmail,
            $request->identityProxy2FAConfirmed(),
        ]);

        return IdentityEmailResource::create($identityEmail->sendVerificationEmail());
    }

    /**
     * Handles the request to set an identity email as primary.
     *
     * This method authorizes the user to make an identity email primary and then sets it as such.
     * It returns a resource representation of the updated identity email.
     *
     * @param BaseFormRequest $request The incoming HTTP request.
     * @param IdentityEmail $identityEmail The identity email to be set as primary.
     * @return IdentityEmailResource A resource representing the updated identity email.
     */
    public function primary(BaseFormRequest $request, IdentityEmail $identityEmail): IdentityEmailResource
    {
        $this->authorize('makePrimary', [
            $identityEmail,
            $request->identityProxy2FAConfirmed(),
        ]);

        return IdentityEmailResource::create($identityEmail->setPrimary());
    }

    /**
     * Deletes the specified identity email.
     *
     * @param BaseFormRequest $request
     * @param IdentityEmail $identityEmail
     * @throws AuthorizationException
     * @return JsonResponse
     */
    public function destroy(BaseFormRequest $request, IdentityEmail $identityEmail): JsonResponse
    {
        $this->authorize('delete', [
            $identityEmail,
            $request->identityProxy2FAConfirmed(),
        ]);

        $identityEmail->delete();

        return new JsonResponse([]);
    }

    /**
     * Redirects to the target URL with email confirmation and verification token parameters.
     *
     * @param IdentityEmail $identityEmail The identity email object containing verification details.
     * @return RedirectResponse A redirect response to the specified URL with query parameters for email confirmation and verification token.
     */
    public function emailVerificationTokenRedirect(IdentityEmail $identityEmail): RedirectResponse
    {
        return redirect($identityEmail->redirect->targetUrl([
            'email_confirmation' => 1,
            'email_confirmation_token' => $identityEmail->verification_token,
        ]));
    }

    /**
     * Verifies an identity email using a verification token.
     *
     * @param IdentityEmail $identityEmail The identity email to verify.
     * @throws AuthorizationException If the user is not authorized to verify the email.
     * @return IdentityEmailResource The resource representation of the verified identity email.
     */
    public function emailVerificationTokenVerify(IdentityEmail $identityEmail): IdentityEmailResource
    {
        $this->authorize('emailTokenVerify', $identityEmail);

        $identityEmail->setVerified();

        return IdentityEmailResource::create($identityEmail->setVerified());
    }
}
