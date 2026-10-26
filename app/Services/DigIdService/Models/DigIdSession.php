<?php

namespace App\Services\DigIdService\Models;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\DigIdService\DigIdException;
use App\Services\DigIdService\DigIdServiceLogger;
use App\Services\DigIdService\Objects\ClientTls;
use App\Services\DigIdService\Objects\DigidAuthRequestData;
use App\Services\DigIdService\Objects\DigidAuthResolveData;
use App\Services\DigIdService\Objects\DigIdResolveContext;
use App\Services\DigIdService\Objects\DigIdSessionData;
use App\Services\DigIdService\Objects\DigIdStartContext;
use App\Services\DigIdService\Repositories\DigIdCgiRepo;
use App\Services\DigIdService\Repositories\DigIdSamlRepo;
use App\Services\DigIdService\Repositories\DigIdSamlTvsRepo;
use App\Services\DigIdService\Repositories\Interfaces\DigIdRepo;
use App\Services\DigIdService\TvsService;
use App\Services\SAML2Service\Responses\SamlArtifactResponse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Throwable;

/**
 * App\Services\DigIdService\Models\DigIdSession.
 *
 * @property int $id
 * @property string $state
 * @property string $connection_type
 * @property int|null $implementation_id
 * @property int|null $organization_id
 * @property string|null $client_type
 * @property string|null $identity_address
 * @property array<array-key, mixed> $meta
 * @property string $session_uid
 * @property string $session_secret
 * @property string $session_final_url
 * @property string $session_request
 * @property string|null $digid_rid
 * @property string|null $digid_uid
 * @property string|null $request_id
 * @property string|null $service_uuid
 * @property string|null $dv_entity_id
 * @property string|null $digid_app_url
 * @property string|null $digid_as_url
 * @property string|null $digid_auth_redirect_url
 * @property string|null $digid_error_code
 * @property string|null $digid_error_message
 * @property string|null $digid_request_aselect_server
 * @property string|null $digid_response_aselect_server
 * @property string|null $digid_response_aselect_credentials
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Identity|null $identity
 * @property-read Implementation|null $implementation
 * @property-read Organization|null $organization
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereClientType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereConnectionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereDestination($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereDigidAppUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereDigidAsUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereDigidAuthRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereDigidErrorCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereDigidErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereDigidRequestAselectServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereDigidResponseAselectCredentials($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereDigidResponseAselectServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereDigidRid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereDigidUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereDvEntityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereRequestId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereServiceUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereSessionFinalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereSessionRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereSessionSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereSessionUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|DigIdSession withoutTrashed()
 * @mixin \Eloquent
 */
class DigIdSession extends Model
{
    use SoftDeletes;

    // Session created
    public const string STATE_CREATED = 'created';
    public const string STATE_EXPIRED = 'expired';
    public const string STATE_PENDING_AUTH = 'pending_authorization';
    public const string STATE_AUTHORIZED = 'authorized';
    public const string STATE_CANCELED = 'canceled';
    public const string STATE_ERROR = 'error';

    public const int SESSION_EXPIRATION_TIME = 10 * 60;
    public const int SESSION_CORRELATION_TIME = 24 * 60 * 60;

    public const string CONNECTION_TYPE_CGI = 'cgi';
    public const string CONNECTION_TYPE_SAML = 'saml';
    public const string CONNECTION_TYPE_TVS = 'tvs';

    public const string SESSION_REQUEST_AUTH = 'auth';
    public const string SESSION_REQUEST_FUND_REQUEST = 'fund_request';

    public const array SESSION_REQUESTS = [self::SESSION_REQUEST_FUND_REQUEST, self::SESSION_REQUEST_AUTH];

    protected $table = 'digid_sessions';

    protected $fillable = [
        'state', 'implementation_id', 'organization_id', 'client_type', 'identity_address', 'meta',
        'connection_type',

        'session_uid', 'session_secret', 'session_final_url',
        'session_request',

        'digid_rid', 'digid_uid', 'digid_app_url', 'digid_as_url',
        'digid_auth_redirect_url', 'digid_error_code',
        'digid_error_message', 'digid_request_aselect_server',
        'digid_response_aselect_server', 'digid_response_aselect_credentials',

        'request_id', 'service_uuid', 'dv_entity_id',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * @return BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @return BelongsTo
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->state == self::STATE_AUTHORIZED;
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        $expiresAt = $this->created_at?->copy()->addSeconds(self::SESSION_EXPIRATION_TIME);

        return !$expiresAt || $expiresAt->isPast();
    }

    /**
     * @return bool
     */
    public function isSessionRequestAuth(): bool
    {
        return $this->session_request === self::SESSION_REQUEST_AUTH;
    }

    /**
     * @return bool
     */
    public function isSessionRequestFundRequest(): bool
    {
        return $this->session_request === self::SESSION_REQUEST_FUND_REQUEST;
    }

    /**
     * @return string
     */
    public function getErrorKey(): string
    {
        return match ($this->digid_error_code) {
            DigIdRepo::ERROR_CANCELLED => 'error_0040',
            '403' => 'error_403',
            default => strtolower($this->digid_error_code ?: 'unknown_error'),
        };
    }

    /**
     * @return Organization|null
     */
    public function sessionOrganization(): ?Organization
    {
        return $this->organization;
    }

    /**
     * @return Identity|null
     */
    public function sessionIdentity(): ?Identity
    {
        return $this->identity;
    }

    /**
     * @return string|null
     */
    public function sessionIdentityBsn(): ?string
    {
        return $this->identity?->bsn;
    }

    /**
     * @return string|null
     */
    public function digidBsn(): ?string
    {
        return $this->digid_uid;
    }

    /**
     * @return Identity|null
     */
    public function digidBsnIdentity(): ?Identity
    {
        return Identity::findByBsn($this->digid_uid);
    }

    /**
     * @param Identity $identity
     * @return static
     */
    public function setIdentity(Identity $identity): static
    {
        return tap($this)->update([
            'identity_address' => $identity->address,
        ])->unsetRelation('identity');
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->state === $this::STATE_PENDING_AUTH;
    }

    /**
     * @param DigIdSessionData $data
     * @throws \Random\RandomException
     * @return DigIdSession
     */
    public static function createSession(DigIdSessionData $data): DigIdSession
    {
        return self::create([
            ...self::makeSessionAttributes($data),
            'connection_type' => $data->connectionType,
            'session_secret' => resolve('token_generator')->generate(200),
            ...($data->connectionType === self::CONNECTION_TYPE_TVS ? [
                'request_id' => '_' . resolve('token_generator')->generate(40),
                'service_uuid' => $data->serviceUuid,
                'dv_entity_id' => $data->dvEntityId,
            ] : []),
        ]);
    }

    /**
     * @return bool
     */
    public function isConnectionTypeTvs(): bool
    {
        return $this->connection_type === self::CONNECTION_TYPE_TVS;
    }

    /**
     * @return string
     */
    public function getTransport(): string
    {
        return $this->isConnectionTypeTvs() ? 'tvs' : 'digid';
    }

    /**
     * @throws Throwable
     * @return DigidAuthRequestData|null
     */
    public function startAuthSession(): ?DigidAuthRequestData
    {
        try {
            $authRequest = $this->makeAuthRequest();
        } catch (DigIdException $exception) {
            $this->setError($exception->getMessage(), $exception->getDigIdCode());

            return null;
        } catch (Throwable $exception) {
            DigIdServiceLogger::logError('Could not start DigiD authentication.', $exception, [
                'connection_type' => $this->connection_type,
                'session_id' => $this->id,
            ]);

            $this->setError('Could not start DigiD authentication.', 'unknown_error');

            return null;
        }

        $this->update([
            'state' => self::STATE_PENDING_AUTH,
            ...($this->isConnectionTypeTvs() ? [
                'request_id' => $authRequest->getRequestId(),
            ] : [
                'digid_auth_redirect_url' => $authRequest->getAuthRedirectUrl(),
                'digid_rid' => $authRequest->getRequestId(),
                'digid_as_url' => $authRequest->getMeta('as_url'),
                'digid_app_url' => $authRequest->getAuthResolveUrl(),
                'digid_request_aselect_server' => $authRequest->getMeta('a-select-server'),
            ]),
        ]);

        return $authRequest;
    }

    /**
     * @return string
     */
    public function getResolveUrl(): string
    {
        return $this->getApiUrl(sprintf('/api/v1/platform/digid/%s/resolve', $this->session_uid));
    }

    /**
     * @param Request|SamlArtifactResponse $response
     * @throws Throwable
     * @return $this
     */
    public function resolveResponse(Request|SamlArtifactResponse $response): self
    {
        try {
            $result = $this->resolveAuthResponse($response);
        } catch (DigIdException $exception) {
            return $this->setError($exception->getMessage(), $exception->getDigIdCode());
        }

        return tap($this)->update([
            'digid_uid' => $result->getUid(),
            'state' => self::STATE_AUTHORIZED,
            ...($this->isConnectionTypeTvs() ? [] : [
                'digid_response_aselect_server' => $result->getMeta('a-select-server'),
                'digid_response_aselect_credentials' => $result->getMeta('resolveParams.aselect_credentials'),
            ]),
        ]);
    }

    /**
     * @param string $message
     * @param string|null $errorCode
     * @return DigIdSession
     */
    public function setError(string $message, ?string $errorCode): self
    {
        $isTvs = $this->isConnectionTypeTvs();

        DigIdServiceLogger::logError("Could not make digid auth request: $errorCode - $message", context: [
            'connection_type' => $this->connection_type,
            'session_id' => $this->id,
        ]);

        $canceled = $errorCode === DigIdRepo::ERROR_CANCELLED;

        return tap($this)->update([
            'digid_error_code' => $errorCode,
            'digid_error_message' => $isTvs ? $message : DigIdCgiRepo::responseCodeDetails($errorCode),
            'state' => $canceled ? self::STATE_CANCELED : self::STATE_ERROR,
        ]);
    }

    /**
     * @param array $tvsConfig
     * @throws DigIdException
     * @return DigIdRepo
     */
    protected function getDigid(array $tvsConfig = []): DigIdRepo
    {
        return match ($this->connection_type) {
            self::CONNECTION_TYPE_SAML => new DigIdSamlRepo($this->implementation->getDigidSamlContext()),
            self::CONNECTION_TYPE_CGI => (new DigIdCgiRepo($this->implementation->digid_env))
                ->setAppId($this->implementation->digid_app_id)
                ->setSharedSecret($this->implementation->digid_shared_secret)
                ->setASelectServer($this->implementation->digid_a_select_server)
                ->setTrustedCertificate($this->implementation->digid_trusted_cert),
            self::CONNECTION_TYPE_TVS => new DigIdSamlTvsRepo(
                $tvsConfig,
                [
                    ...$this->organization->getTvsDigidConfig(),
                    'entity_id' => $this->dv_entity_id,
                    'service_uuid' => $this->service_uuid,
                ],
            ),
            default => throw new InvalidArgumentException('Unsupported DigiD session protocol.'),
        };
    }

    /**
     * @throws Throwable
     * @return DigidAuthRequestData
     */
    protected function makeAuthRequest(): DigidAuthRequestData
    {
        $tvsService = $this->isConnectionTypeTvs() ? resolve(TvsService::class) : null;
        $configs = $tvsService?->makeSamlConfig() ?? [];
        $digid = $this->getDigid($configs);

        $context = $tvsService
            ? new DigIdStartContext(
                callbackUrl: Arr::get($configs, 'sp.assertionConsumerService.url'),
                requestId: $this->request_id,
            )
            : new DigIdStartContext(
                $this->getResolveUrl(),
                $this->session_secret,
                $this->getClientCert(),
            );

        return $digid->makeAuthRequest($context);
    }

    /**
     * @param Request|SamlArtifactResponse $response
     * @throws Throwable
     * @return DigidAuthResolveData
     */
    protected function resolveAuthResponse(Request|SamlArtifactResponse $response): DigidAuthResolveData
    {
        $isTvs = $this->isConnectionTypeTvs();

        try {
            return $this->getDigid()->resolveResponse(
                $response,
                new DigIdResolveContext(
                    requestId: $isTvs ? $this->request_id : $this->digid_rid,
                    sessionSecret: $isTvs ? null : $this->session_secret,
                    tlsCert: $isTvs ? null : $this->getClientCert(),
                ),
            );
        } catch (Throwable $exception) {
            if ($exception instanceof DigIdException) {
                throw $exception;
            }

            DigIdServiceLogger::logError('Could not resolve DigiD authentication response.', $exception, [
                'connection_type' => $this->connection_type,
                'session_id' => $this->id,
            ]);

            throw DigIdException::make(
                'Could not resolve DigiD authentication response.',
                'unknown_error',
                $exception,
            );
        }
    }

    /**
     * @return ClientTls|null
     */
    protected function getClientCert(): ?ClientTls
    {
        $implementation = $this->implementation;
        $generalImplementation = Implementation::general();

        if ($implementation->digid_cgi_tls_cert && $implementation->digid_cgi_tls_key) {
            return new ClientTls($implementation->digid_cgi_tls_key, $implementation->digid_cgi_tls_cert);
        } elseif ($generalImplementation->digid_cgi_tls_cert && $generalImplementation->digid_cgi_tls_key) {
            return new ClientTls($generalImplementation->digid_cgi_tls_key, $generalImplementation->digid_cgi_tls_cert);
        }

        return null;
    }

    /**
     * @param string $uri
     * @return string
     */
    protected function getApiUrl(string $uri): string
    {
        $implementationApiUrl = $this->implementation->digid_forus_api_url;
        $apiHost = $implementationApiUrl ?: url('/');

        return sprintf('%s/%s', rtrim($apiHost, '/'), ltrim($uri, '/'));
    }

    /**
     * @param DigIdSessionData $data
     * @return array<string, mixed>
     */
    protected static function makeSessionAttributes(DigIdSessionData $data): array
    {
        return [
            'client_type' => $data->clientType,
            'identity_address' => $data->identityAddress,
            'implementation_id' => $data->implementationId,
            'organization_id' => $data->organizationId,
            'state' => self::STATE_CREATED,
            'session_uid' => resolve('token_generator')->generate(64),
            'session_final_url' => $data->sessionFinalUrl,
            'session_request' => $data->sessionRequest,
            'meta' => self::makeSessionMeta($data),
        ];
    }

    /**
     * @param DigIdSessionData $data
     * @return array
     */
    private static function makeSessionMeta(DigIdSessionData $data): array
    {
        return [
            ...($data->sessionRequest === self::SESSION_REQUEST_FUND_REQUEST ? ['fund_id' => $data->fundId] : []),
            'browser_challenge' => $data->browserChallenge,
        ];
    }
}
