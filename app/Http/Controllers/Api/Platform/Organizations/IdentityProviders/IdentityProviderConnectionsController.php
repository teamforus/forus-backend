<?php

namespace App\Http\Controllers\Api\Platform\Organizations\IdentityProviders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\IdentityProviders\Connections\Events\IndexIdentityProviderEventsRequest;
use App\Http\Requests\Api\Platform\Organizations\IdentityProviders\Connections\IndexIdentityProviderConnectionsHistoryRequest;
use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderHttpFailure;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Resources\IdentityProviderConnectionHistoryResource;
use App\Services\IdentityProviderService\Resources\IdentityProviderConnectionResource;
use App\Services\IdentityProviderService\Resources\IdentityProviderEventResource;
use App\Services\IdentityProviderService\Services\IdentityProviderConnectionService;
use App\Services\IdentityProviderService\Services\IdentityProviderLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class IdentityProviderConnectionsController extends Controller
{
    /**
     * @param IdentityProviderConnectionService $connectionService
     */
    public function __construct(
        protected IdentityProviderConnectionService $connectionService,
    ) {
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @return JsonResponse
     */
    public function current(
        BaseFormRequest $request,
        Organization $organization,
    ): JsonResponse {
        $this->authorize('manage', [IdentityProviderConnection::class, $organization, $request->identityProxy()]);

        $connection = $organization->identity_provider_connection;

        return new JsonResponse([
            'data' => $connection ? IdentityProviderConnectionResource::create($connection)->resolve($request) : null,
        ]);
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param IdentityProviderConnection $connection
     * @return IdentityProviderConnectionResource
     */
    public function show(
        BaseFormRequest $request,
        Organization $organization,
        IdentityProviderConnection $connection,
    ): IdentityProviderConnectionResource {
        $this->authorize('manage', [
            IdentityProviderConnection::class, $organization, $request->identityProxy(), $connection,
        ]);

        return IdentityProviderConnectionResource::create($connection);
    }

    /**
     * @param IndexIdentityProviderConnectionsHistoryRequest $request
     * @param Organization $organization
     * @return AnonymousResourceCollection
     */
    public function history(
        IndexIdentityProviderConnectionsHistoryRequest $request,
        Organization $organization,
    ): AnonymousResourceCollection {
        $this->authorize('manage', [IdentityProviderConnection::class, $organization, $request->identityProxy()]);

        return IdentityProviderConnectionHistoryResource::queryCollection(
            $organization->identity_provider_connections_disconnected()->latest('disconnected_at')->latest('id'),
            $request,
        );
    }

    /**
     * @param IndexIdentityProviderEventsRequest $request
     * @param Organization $organization
     * @param IdentityProviderConnection $connection
     * @return AnonymousResourceCollection
     */
    public function events(
        IndexIdentityProviderEventsRequest $request,
        Organization $organization,
        IdentityProviderConnection $connection,
    ): AnonymousResourceCollection {
        $this->authorize('manage', [
            IdentityProviderConnection::class, $organization, $request->identityProxy(), $connection,
        ]);

        return IdentityProviderEventResource::queryCollection(
            $connection->logs()->latest('created_at')->latest('id'),
            $request,
        );
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @return JsonResponse
     */
    public function startConsent(
        BaseFormRequest $request,
        Organization $organization,
    ): JsonResponse {
        $this->authorize('manage', [IdentityProviderConnection::class, $organization, $request->identityProxy()]);

        try {
            $finalUrl = (string) Implementation::general()->urlFrontend(
                $request->client_type(),
                sprintf('/organisaties/%s/single-sign-on', $organization->id),
            );

            $session = $this
                ->connectionService
                ->startConnectionConsent($organization, $request->identity(), $finalUrl);
        } catch (Throwable $exception) {
            throw IdentityProviderHttpFailure::toException(
                $exception,
                operation: IdentityProviderLogService::OPERATION_ADMIN_CONSENT_START,
                context: IdentityProviderLogService::ownerContext($organization, $organization->identity_provider_connection),
                fallbackCode: IdentityProviderErrorCode::ADMIN_CONSENT_START_FAILED,
                fallbackMessage: __('exceptions.identity_providers.admin_consent_start_failed'),
            );
        }

        return new JsonResponse([
            'data' => [
                'redirect_url' => $session->oidc_authorization_url,
            ],
        ], 201);
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param IdentityProviderConnection $connection
     * @return IdentityProviderConnectionResource
     */
    public function pause(
        BaseFormRequest $request,
        Organization $organization,
        IdentityProviderConnection $connection,
    ): IdentityProviderConnectionResource {
        return $this->changeState('pause', $request, $organization, $connection);
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param IdentityProviderConnection $connection
     * @return IdentityProviderConnectionResource
     */
    public function resume(
        BaseFormRequest $request,
        Organization $organization,
        IdentityProviderConnection $connection,
    ): IdentityProviderConnectionResource {
        return $this->changeState('resume', $request, $organization, $connection);
    }

    /**
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param IdentityProviderConnection $connection
     * @return IdentityProviderConnectionResource
     */
    public function disconnect(
        BaseFormRequest $request,
        Organization $organization,
        IdentityProviderConnection $connection,
    ): IdentityProviderConnectionResource {
        return $this->changeState('disconnect', $request, $organization, $connection);
    }

    /**
     * @param string $action
     * @param BaseFormRequest $request
     * @param Organization $organization
     * @param IdentityProviderConnection $connection
     * @return IdentityProviderConnectionResource
     */
    protected function changeState(
        string $action,
        BaseFormRequest $request,
        Organization $organization,
        IdentityProviderConnection $connection,
    ): IdentityProviderConnectionResource {
        $this->authorize('manage', [
            IdentityProviderConnection::class, $organization, $request->identityProxy(), $connection,
        ]);

        $operation = match ($action) {
            'pause' => IdentityProviderLogService::OPERATION_CONNECTION_PAUSE,
            'resume' => IdentityProviderLogService::OPERATION_CONNECTION_RESUME,
            'disconnect' => IdentityProviderLogService::OPERATION_CONNECTION_DISCONNECT,
        };

        try {
            $connection = match ($action) {
                'pause' => $this->connectionService->pause($connection, $request->identity()),
                'resume' => $this->connectionService->resume($connection, $request->identity()),
                'disconnect' => $this->connectionService->disconnect($connection, $request->identity()),
            };
        } catch (Throwable $exception) {
            throw IdentityProviderHttpFailure::toException(
                $exception,
                operation: $operation,
                context: IdentityProviderLogService::ownerContext($organization, $connection),
                fallbackCode: match ($action) {
                    'pause' => IdentityProviderErrorCode::CONNECTION_PAUSE_FAILED,
                    'resume' => IdentityProviderErrorCode::CONNECTION_RESUME_FAILED,
                    'disconnect' => IdentityProviderErrorCode::CONNECTION_DISCONNECT_FAILED,
                },
                fallbackMessage: match ($action) {
                    'pause' => __('exceptions.identity_providers.connection_pause_failed'),
                    'resume' => __('exceptions.identity_providers.connection_resume_failed'),
                    'disconnect' => __('exceptions.identity_providers.connection_disconnect_failed'),
                },
            );
        }

        return IdentityProviderConnectionResource::create($connection);
    }
}
