<?php

namespace App\Http\Controllers;

use App\Http\Requests\DigID\CompleteDigIdRequest;
use App\Http\Requests\Tvs\ResolveTvsRequest;
use App\Http\Requests\Tvs\StartTvsRequest;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\DigIdService\Controllers\BaseDigIdController;
use App\Services\DigIdService\DigIdException;
use App\Services\DigIdService\DigIdServiceLogger;
use App\Services\DigIdService\Models\DigIdSession;
use App\Services\DigIdService\TvsService;
use App\Services\SAML2Service\Exceptions\Saml2Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Throwable;

class TvsController extends BaseDigIdController
{
    /**
     * @param StartTvsRequest $request
     * @throws Throwable
     * @return JsonResponse
     */
    public function start(StartTvsRequest $request): JsonResponse
    {
        $session = DigIdSession::createSession($request->sessionData());
        $authRequest = $session->startAuthSession();

        return $this->makeStartResponse($session, $authRequest);
    }

    /**
     * @param ResolveTvsRequest $request
     * @throws Throwable
     * @return RedirectResponse
     */
    public function resolve(ResolveTvsRequest $request): RedirectResponse
    {
        $tvsService = resolve(TvsService::class);

        try {
            $data = $tvsService->resolveResponseFromRequest($request);
        } catch (DigIdException $exception) {
            $result = $tvsService->resolveErrorFromRelayState($request->input('RelayState'), $exception);

            return $result
                ? $this->makeRedirectErrorResponse($result['session'], $result['error'])
                : redirect(Implementation::general()->urlWebshop());
        }

        return $this->resolveSession($data['session'], $data['response']);
    }

    /**
     * @param CompleteDigIdRequest $request
     * @throws Throwable
     * @return JsonResponse
     */
    public function complete(CompleteDigIdRequest $request): JsonResponse
    {
        return $this->completeSession($request, 'tvs');
    }

    /**
     * @return Response
     */
    public function metadata(): Response
    {
        $tvsService = resolve(TvsService::class);

        $dvEntities = Organization::whereNotNull('tvs_digid_config')
            ->get()
            ->map(function (Organization $organization) {
                $configuration = $organization->getTvsDigidConfig();

                return [
                    'entityID' => $configuration['entity_id'],
                    'x509cert' => $configuration['certificate'],
                ];
            })
            ->toArray();

        if (empty($dvEntities)) {
            DigIdServiceLogger::logError(
                'TVS metadata requested but no organizations have an EntityID configured yet.',
                context: ['connection_type' => DigIdSession::CONNECTION_TYPE_TVS],
            );

            return response('No TVS-enabled organizations configured.', 503);
        }

        try {
            $metadata = $tvsService->makeMetadata($dvEntities);
        } catch (Saml2Exception $exception) {
            DigIdServiceLogger::logError('TVS metadata generation failed.', $exception, [
                'connection_type' => DigIdSession::CONNECTION_TYPE_TVS,
            ]);

            return response('Metadata temporarily unavailable.', 503);
        }

        return response($metadata, 200)->header('Content-Type', 'application/samlmetadata+xml');
    }
}
