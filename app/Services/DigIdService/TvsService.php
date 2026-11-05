<?php

namespace App\Services\DigIdService;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\DigIdService\Models\DigIdSession;
use App\Services\DigIdService\Traits\BuildsSamlConfig;
use App\Services\SAML2Service\Exceptions\InvalidConfigsException;
use App\Services\SAML2Service\Exceptions\Saml2Exception;
use App\Services\SAML2Service\Responses\SamlArtifactResponse;
use App\Services\SAML2Service\SamlAuth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class TvsService
{
    use BuildsSamlConfig;

    /**
     * @param Builder<Fund> $fundsQuery
     * @return Builder<Organization>
     */
    public function eligibleOrganizationsQuery(Builder $fundsQuery): Builder
    {
        return Organization::query()
            ->where('bsn_enabled', true)
            ->whereNotNull('tvs_digid_config')
            ->whereIn('id', (clone $fundsQuery)->select('organization_id'));
    }

    /**
     * @param array $configuration
     * @throws ValidationException
     * @return array
     */
    public function validateConfiguration(array $configuration): array
    {
        return Validator::make(['configuration' => $configuration], [
            'configuration' => 'required|array:entity_id,service_uuid,authn_context,certificate,private_key',
            'configuration.entity_id' => 'required|string|max:255',
            'configuration.service_uuid' => 'required|string|max:255',
            'configuration.authn_context' => 'required|string|max:255',
            'configuration.certificate' => 'required|string',
            'configuration.private_key' => 'required|string',
        ])->validate()['configuration'];
    }

    /**
     * @param Request $request
     * @throws DigIdException
     * @return array{session: DigIdSession, response: SamlArtifactResponse}
     */
    public function resolveResponseFromRequest(Request $request): array
    {
        try {
            $auth = SamlAuth::make($this->makeSamlConfig());
        } catch (Saml2Exception $e) {
            throw DigIdException::make('Build SamlAuth failed. ' . $e->getMessage(), 'unknown_error');
        }

        if (!$request->filled('SAMLart')) {
            throw DigIdException::make('Missing SAMLart.', 'unknown_error');
        }

        try {
            $response = $auth->resolveArtifact($request->input('SAMLart'));
        } catch (Saml2Exception $e) {
            throw DigIdException::make('Artifact resolution failed. ' . $e->getMessage(), 'unknown_error');
        }

        $requestId = $response->getInResponseTo();

        if ($requestId === null || $requestId === '') {
            throw DigIdException::make('Missing InResponseTo in response.', 'unknown_error');
        }

        try {
            $session = DigIdSession::query()
                ->where('request_id', $requestId)
                ->where('connection_type', DigIdSession::CONNECTION_TYPE_TVS)
                ->where('state', DigIdSession::STATE_PENDING_AUTH)
                ->where('created_at', '>=', now()->subSeconds(DigIdSession::SESSION_EXPIRATION_TIME))
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            throw DigIdException::make('Related session not found in response.', 'unknown_error');
        }

        try {
            // set actual DV settings to response for future assert and decrypt BSN
            $response->setSettings(SamlAuth::make($this->makeSamlConfig([
                'sp.entityId' => $session->dv_entity_id,
                'sp.privateKey' => $session->organization->getTvsDigidConfig()['private_key'],
            ]))->getSettings());
        } catch (ValidationException) {
            throw DigIdException::make('Invalid TVS DigiD configuration.', 'unknown_error');
        } catch (InvalidConfigsException) {
            throw DigIdException::make('Setting DV failed.', 'unknown_error');
        } catch (Saml2Exception) {
            throw DigIdException::make('Build SamlAuth for DV failed.', 'unknown_error');
        }

        return [
            'session' => $session,
            'response' => $response,
        ];
    }

    /**
     * @param string|null $relayState
     * @param DigIdException $exception
     * @throws Throwable
     * @return array{session: DigIdSession, error: string}|null
     */
    public function resolveErrorFromRelayState(?string $relayState, DigIdException $exception): ?array
    {
        return DB::transaction(function () use ($relayState, $exception) {
            // Recover the redirect destination even when the session is expired or deleted.
            $session = $this->resolveSessionFromRelayState($relayState);

            if (!$session) {
                return null;
            }

            if ($session->trashed() || !$session->isPending() || $session->isExpired()) {
                return ['session' => $session, 'error' => 'unknown_error'];
            }

            $session->setError($exception->getMessage(), $exception->getDigIdCode());

            return ['session' => $session, 'error' => $session->getErrorKey()];
        });
    }

    /**
     * @param array<array{entityID: string, x509cert: string}> $dvEntities
     * @throws Saml2Exception
     * @return string
     */
    public function makeMetadata(array $dvEntities): string
    {
        $settings = SamlAuth::make($this->makeSamlConfig())->getSettings();

        return TvsMetadataBuilder::make()->buildTvsMetadataForEntities($dvEntities, $settings);
    }

    /**
     * @param array $replace
     * @return array
     */
    public function makeSamlConfig(array $replace = []): array
    {
        $implementation = Implementation::general();

        return $this->mergeSamlConfig(Config::get('saml-tvs'), [
            'sp' => [
                'x509cert' => $implementation->digid_tvs_sp_cert,
                'privateKey' => $implementation->digid_tvs_sp_private_key,
            ],
            'idp' => [
                'certData' => $implementation->digid_tvs_idp_cert_data,
                'x509cert' => $implementation->digid_tvs_idp_cert,
            ],
        ], $replace);
    }

    /**
     * @param string|null $relayState
     * @return DigIdSession|null
     */
    protected function resolveSessionFromRelayState(?string $relayState): ?DigIdSession
    {
        if (!$relayState) {
            return null;
        }

        return DigIdSession::withTrashed()
            ->where('session_uid', $relayState)
            ->where('connection_type', DigIdSession::CONNECTION_TYPE_TVS)
            ->where('created_at', '>=', now()->subSeconds(DigIdSession::SESSION_CORRELATION_TIME))
            ->lockForUpdate()
            ->first();
    }
}
