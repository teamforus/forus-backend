<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\IdentityProviders\CompleteIdentityProviderLinkRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Responses\NoContentResponse;
use App\Models\Implementation;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderErrorCode;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderException;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderHttpException;
use App\Services\IdentityProviderService\Exceptions\IdentityProviderHttpFailure;
use App\Services\IdentityProviderService\Models\IdentityProviderConnection;
use App\Services\IdentityProviderService\Models\IdentityProviderMembership;
use App\Services\IdentityProviderService\Models\IdentityProviderOidcSession;
use App\Services\IdentityProviderService\Queries\IdentityProviderConnectionQuery;
use App\Services\IdentityProviderService\Resources\IdentityProviderUserLinkResource;
use App\Services\IdentityProviderService\Responses\IdentityProviderOidcStartResponse;
use App\Services\IdentityProviderService\Services\IdentityProviderAccountLinkService;
use App\Services\IdentityProviderService\Services\IdentityProviderLogService;
use App\Services\IdentityProviderService\Services\IdentityProviderOidcService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Throwable;

class IdentityProviderLinksController extends Controller
{
    /**
     * @param IdentityProviderAccountLinkService $accountLinkService
     * @param IdentityProviderLogService $logService
     */
    public function __construct(
        protected IdentityProviderAccountLinkService $accountLinkService,
        protected IdentityProviderLogService $logService,
    ) {
    }

    /**
     * @param BaseFormRequest $request
     * @return AnonymousResourceCollection
     */
    public function index(BaseFormRequest $request): AnonymousResourceCollection
    {
        $management = Gate::forUser($request->identity())->inspect('manageLinks', [
            IdentityProviderMembership::class,
            $request->identityProxy(),
        ]);

        $links = IdentityProviderUserLinkResource::queryCollection(
            $request->identity()->identity_provider_memberships_claimed()->orderByDesc('consented_at'),
            $request,
        );

        $available_connections = $management->allowed() ? IdentityProviderConnectionQuery::whereAvailableForSelfLink(
            IdentityProviderConnection::query(),
            $request->identity(),
        )
            ->with('organization:id,name')
            ->orderBy('organization_id')
            ->get()
            ->map(fn (IdentityProviderConnection $connection) => [
                ...$connection->only(['uid', 'provider']),
                'organization' => $connection->organization->only(['id', 'name']),
            ])->values() : [];

        return $links->additional([
            'meta' => [
                'can_manage_links' => $management->allowed(),
                'management_message' => $management->message(),
                'available_connections' => $available_connections,
            ],
        ]);
    }

    /**
     * @param BaseFormRequest $request
     * @param IdentityProviderConnection $connection
     * @return JsonResponse
     */
    public function store(BaseFormRequest $request, IdentityProviderConnection $connection): JsonResponse
    {
        $this->authorize('link', [IdentityProviderMembership::class, $request->identityProxy(), $connection]);

        try {
            $browserToken = bin2hex(random_bytes(32));
            $finalUrl = Implementation::general()->urlFrontend(
                $request->client_type(),
                '/beveiliging/gekoppelde-accounts',
            );

            $session = resolve(IdentityProviderOidcService::class)->startSelfLink(
                $connection,
                $request->identity(),
                (string) $finalUrl,
                $browserToken,
            );
        } catch (Throwable $exception) {
            throw IdentityProviderHttpFailure::toException(
                $exception,
                operation: IdentityProviderLogService::OPERATION_ACCOUNT_LINK_START,
                context: [
                    'organization_id' => $connection->organization_id,
                    'connection_id' => $connection->id,
                    'tenant_id' => $connection->tenant_id,
                ],
                fallbackCode: IdentityProviderErrorCode::ACCOUNT_LINK_START_FAILED,
                fallbackMessage: __('exceptions.identity_providers.account_link_start_failed'),
            );
        }

        return new IdentityProviderOidcStartResponse($session, $browserToken);
    }

    /**
     * @param CompleteIdentityProviderLinkRequest $request
     * @param IdentityProviderOidcSession $session
     * @return NoContentResponse
     */
    public function complete(CompleteIdentityProviderLinkRequest $request, IdentityProviderOidcSession $session): NoContentResponse
    {
        $this->authorize('completeLink', [IdentityProviderMembership::class, $request->identityProxy(), $session]);

        try {
            $this->accountLinkService->completeSelfLink(
                $session,
                $request->identityProxy(),
                $request->string('exchange_token')->toString(),
                $request->string('browser_token')->toString(),
            );
        } catch (Throwable $exception) {
            throw IdentityProviderHttpFailure::toException(
                $exception,
                operation: IdentityProviderLogService::OPERATION_ACCOUNT_LINK_COMPLETE,
                context: IdentityProviderLogService::sessionContext($session),
                fallbackCode: IdentityProviderErrorCode::ACCOUNT_LINK_COMPLETE_FAILED,
                fallbackMessage: __('exceptions.identity_providers.account_link_complete_failed'),
            );
        }

        return new NoContentResponse();
    }

    /**
     * @param BaseFormRequest $request
     * @param IdentityProviderMembership $link
     * @throws Throwable
     * @return NoContentResponse
     */
    public function destroy(
        BaseFormRequest $request,
        IdentityProviderMembership $link,
    ): NoContentResponse {
        $this->authorize('unlink', [$link, $request->identityProxy()]);

        try {
            $this->accountLinkService->selfUnlink($link, $request->identity());
        } catch (IdentityProviderException $exception) {
            $diagnosticId = $this->logService->exceptionFailure(IdentityProviderLogService::OPERATION_ACCOUNT_LINK_UNLINK, $exception, [
                'connection_id' => $link->connection_id,
                'membership_id' => $link->id,
            ]);

            throw new IdentityProviderHttpException(409, $exception->getMessage(), $diagnosticId);
        }

        return new NoContentResponse();
    }
}
