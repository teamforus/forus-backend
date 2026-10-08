<?php

namespace App\Http\Controllers\Api\Platform\Organizations\IdentityProviders;

use App\Http\Controllers\Controller;
use App\Http\Requests\BaseFormRequest;
use App\Http\Responses\NoContentResponse;
use App\Models\Organization;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderHttpFailure;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderScimCredential;
use App\Services\IdentityProviderService\Resources\IdentityProviderScimCredentialResource;
use App\Services\IdentityProviderService\Services\IdentityProviderConnectionService;
use App\Services\IdentityProviderService\Services\IdentityProviderLogService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Throwable;

class IdentityProviderScimCredentialsController extends Controller
{
    /**
     * @param IdentityProviderConnectionService $connectionService
     */
    public function __construct(protected IdentityProviderConnectionService $connectionService)
    {
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param IdentityProviderConnection $connection
     * @throws AuthorizationException
     * @return JsonResponse
     */
    public function store(
        BaseFormRequest $request,
        Organization $organization,
        IdentityProviderConnection $connection,
    ): JsonResponse {
        $this->authorize('manageProvisioning', [
            IdentityProviderConnection::class, $organization, $request->identityProxy(), $connection,
        ]);

        try {
            $result = $this->connectionService->issueScimCredential($connection, $request->identity());
        } catch (Throwable $exception) {
            throw IdentityProviderHttpFailure::toException(
                $exception,
                operation: IdentityProviderLogService::OPERATION_SCIM_CREDENTIAL_ISSUE,
                context: IdentityProviderLogService::ownerContext($organization, $connection),
                fallbackCode: IdentityProviderErrorCode::SCIM_CREDENTIAL_ISSUE_FAILED,
                fallbackMessage: __('exceptions.identity_providers.scim_credential_issue_failed'),
            );
        }

        return new JsonResponse([
            'data' => [
                ...IdentityProviderScimCredentialResource::create($result['credential'])->resolve($request),
                'token' => $result['token'],
            ],
        ], 201, ['Cache-Control' => 'no-store']);
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param IdentityProviderConnection $connection
     * @param IdentityProviderScimCredential $credential
     * @throws AuthorizationException
     * @return NoContentResponse
     */
    public function destroy(
        BaseFormRequest $request,
        Organization $organization,
        IdentityProviderConnection $connection,
        IdentityProviderScimCredential $credential,
    ): NoContentResponse {
        $this->authorize('manageProvisioning', [
            IdentityProviderConnection::class, $organization, $request->identityProxy(), $connection, $credential,
        ]);

        try {
            $this->connectionService->revokeScimCredential($connection, $credential, $request->identity());
        } catch (Throwable $exception) {
            throw IdentityProviderHttpFailure::toException(
                $exception,
                operation: IdentityProviderLogService::OPERATION_SCIM_CREDENTIAL_REVOKE,
                context: IdentityProviderLogService::ownerContext($organization, $connection),
                fallbackCode: IdentityProviderErrorCode::SCIM_CREDENTIAL_REVOKE_FAILED,
                fallbackMessage: __('exceptions.identity_providers.scim_credential_revoke_failed'),
            );
        }

        return new NoContentResponse();
    }
}
