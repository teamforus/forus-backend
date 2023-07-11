<?php

namespace App\Services\DigIdService\Models;

use App\Http\Requests\DigID\ResolveDigIdRequest;
use App\Http\Requests\DigID\StartDigIdRequest;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\DigIdService\DigIdException;
use App\Services\DigIdService\Objects\ClientTls;
use App\Services\DigIdService\Repositories\DigIdCgiRepo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

/**
 * App\Services\DigIdService\Models\DigIdSession
 *
 * @property int $id
 * @property string $state
 * @property string $connection_type
 * @property int|null $implementation_id
 * @property string|null $client_type
 * @property string|null $identity_address
 * @property array $meta
 * @property string $session_uid
 * @property string $session_secret
 * @property string $session_final_url
 * @property string $session_request
 * @property string|null $digid_rid
 * @property string|null $digid_uid
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
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession newQuery()
 * @method static \Illuminate\Database\Query\Builder|DigIdSession onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession query()
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereClientType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereConnectionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereDigidAppUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereDigidAsUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereDigidAuthRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereDigidErrorCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereDigidErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereDigidRequestAselectServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereDigidResponseAselectCredentials($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereDigidResponseAselectServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereDigidRid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereDigidUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereSessionFinalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereSessionRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereSessionSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereSessionUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|DigIdSession withTrashed()
 * @method static \Illuminate\Database\Query\Builder|DigIdSession withoutTrashed()
 * @mixin \Eloquent
 */
class DigIdSession extends Model
{
    use SoftDeletes;

    // Session created
    public const STATE_CREATED         = 'created';
    // Session expired
    public const STATE_EXPIRED         = 'expired';
    // Session created and rid received from digid
    public const STATE_PENDING_AUTH    = 'pending_authorization';
    // User authorized session through on digid auth form
    public const STATE_AUTHORIZED      = 'authorized';
    // User canceled digid request
    public const STATE_CANCELED        = 'canceled';
    // Session has error and can't be used anymore
    public const STATE_ERROR           = 'error';

    // List all valid states
    public const STATES = [
        self::STATE_CREATED,
        self::STATE_EXPIRED,
        self::STATE_PENDING_AUTH,
        self::STATE_CANCELED,
        self::STATE_AUTHORIZED,
        self::STATE_ERROR,
    ];

    // Sessions which are authorized in 10 minutes are deleted
    public const SESSION_EXPIRATION_TIME = 10*60;

    public const CONNECTION_TYPE_CGI = 'cgi';
    public const CONNECTION_TYPE_SAML = 'saml';

    protected $table = 'digid_sessions';

    protected $fillable = [
        'state', 'implementation_id', 'client_type', 'identity_address', 'meta',
        'connection_type',

        'session_uid', 'session_secret', 'session_final_url',
        'session_request',

        'digid_rid', 'digid_uid', 'digid_app_url', 'digid_as_url',
        'digid_auth_redirect_url', 'digid_error_code',
        'digid_error_message', 'digid_request_aselect_server',
        'digid_response_aselect_server', 'digid_response_aselect_credentials',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @param array $attributes
     * @param array $options
     * @return $this
     */
    public function updateModel(array $attributes = [], array $options = []): self
    {
        $this->update($attributes, $options);

        return $this;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @param StartDigIdRequest $request
     * @return DigIdSession
     */
    public static function createSession(StartDigIdRequest $request): DigIdSession
    {
        $token_generator = resolve('token_generator');
        $implementation = $request->implementation();

        return self::create([
            'client_type'           => $request->client_type(),
            'identity_address'      => $request->auth_address(),
            'implementation_id'     => $implementation->id,
            'connection_type'       => $implementation->digid_connection_type,
            'state'                 => DigIdSession::STATE_CREATED,
            'session_uid'           => $token_generator->generate(100),
            'session_secret'        => $token_generator->generate(200),
            'session_final_url'     => self::makeFinalRedirectUrl($request),
            'session_request'       => $request->input('request'),
            'meta'                  => self::makeSessionMeta($request),
        ]);
    }

    /**
     * @param string $uri
     * @param array $params
     * @return string
     */
    protected function getApiUrl(string $uri, array $params = []): string
    {
        $implementationApiUrl = $this->implementation->digid_forus_api_url;
        $apiHost = $implementationApiUrl ?: url('/');
        $url = sprintf('%s/%s', rtrim($apiHost, '/'), ltrim($uri, '/'));

        return !empty($params) ? url_extend_get_params($url, $params) : $url;
    }

    /**
     * @return $this
     */
    public function startAuthSession(): self
    {
        try {
            $digid = $this->implementation->getDigid();
            $authRequest = $digid->makeAuthRequest(
                $this->getResolveUrl(),
                $this->session_secret,
                $this->getClientCert(),
            );
        } catch (DigIdException $exception) {
            $this->setError($exception->getMessage(), $exception->getDigIdCode());
            return $this;
        }

        return $this->updateModel([
            'state'                         => self::STATE_PENDING_AUTH,
            'digid_rid'                     => $authRequest->getRequestId(),
            'digid_state'                   => DigIdSession::STATE_PENDING_AUTH,
            'digid_as_url'                  => $authRequest->getMeta('as_url'),
            'digid_app_url'                 => $authRequest->getAuthResolveUrl(),
            'digid_request_aselect_server'  => $authRequest->getMeta('a-select-server'),
            'digid_auth_redirect_url'       => $authRequest->getAuthRedirectUrl()
        ]);
    }

    /**
     * @param $message
     * @param $errorCode
     * @return DigIdSession
     */
    private function setError($message, $errorCode): self
    {
        Log::error(sprintf('Could not make digid auth request, got %s: %s', $errorCode, $message));
        $canceled = $errorCode == DigIdCgiRepo::DIGID_CANCELLED;

        return $this->updateModel([
            'digid_error_code'      => $errorCode,
            'digid_error_message'   => DigIdCgiRepo::responseCodeDetails($errorCode),
            'state'                 => $canceled ? self::STATE_CANCELED: self::STATE_ERROR,
        ]);
    }

    /**
     * @param string $state
     * @return bool
     */
    public function setState(string $state): bool
    {
        return $this->update([
            'state' => $state,
        ]);
    }

    /**
     * @return bool
     */
    public function isAuthorized(): bool
    {
        return $this->state == self::STATE_AUTHORIZED;
    }

    /**
     * @return string
     */
    public function getErrorKey(): string
    {
        return $this->digid_error_code ? "error_$this->digid_error_code" : 'unknown_error';
    }

    /**
     * @param array $params
     * @return string
     */
    public function getRedirectUrl(array $params = []): string
    {
        return $this->getApiUrl(sprintf('/api/v1/platform/digid/%s/redirect', $this->session_uid), $params);
    }

    /**
     * @param array $params
     * @return string
     */
    public function getResolveUrl(array $params = []): string
    {
        return $this->getApiUrl(sprintf('/api/v1/platform/digid/%s/resolve', $this->session_uid), $params);
    }

    /**
     * @param StartDigIdRequest $request
     * @return string|null
     */
    protected static function makeFinalRedirectUrl(StartDigIdRequest $request): ?string
    {
        if ($request->input('request') === 'fund_request') {
            $fund = Fund::find($request->input('fund_id'));

            return $fund->urlWebshop(sprintf('/fondsen/%s/activeer', $fund->id));
        }

        if (($request->input('request') === 'auth')) {
            return $request->implementation()->urlFrontend($request->client_type());
        }

        return null;
    }

    /**
     * @param StartDigIdRequest $request
     * @return array
     */
    private static function makeSessionMeta(StartDigIdRequest $request): array
    {
        if ($request->input('request') === 'fund_request') {
            return $request->only('fund_id');
        }

        return [];
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
     * @return Organization|null
     */
    public function sessionOrganization(): ?Organization
    {
        $fund = Fund::find($this->meta['fund_id'] ?? null);

        return $fund?->organization ?: $this->implementation->organization;
    }

    /**
     * @param array $data
     * @param string|null $url
     * @return RedirectResponse
     */
    public function makeRedirectResponse(array $data, string $url = null): RedirectResponse
    {
        return redirect(url_extend_get_params($url ?: $this->session_final_url, $data));
    }

    /**
     * @param string $error
     * @param string|null $url
     * @return RedirectResponse
     */
    public function makeRedirectErrorResponse(string $error, string $url = null): RedirectResponse
    {
        return $this->makeRedirectResponse([
            'digid_error' => $error,
        ], $url);
    }

    /**
     * @param Identity $identity
     * @return Model|$this
     */
    public function setIdentity(Identity $identity): Model|DigIdSession
    {
        return $this->updateModel([
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
     * @param ResolveDigIdRequest $request
     * @return $this
     */
    public function resolveResponse(ResolveDigIdRequest $request): self
    {
        try {
            $result = $this->implementation->getDigid()->resolveResponse(
                $request,
                $this->digid_rid,
                $this->session_secret,
                $this->getClientCert(),
            );
        } catch (DigIdException $exception) {
            return $this->setError($exception->getMessage(), $exception->getDigIdCode());
        }

        return $this->updateModel([
            'digid_uid'                             => $result->getUid(),
            'digid_response_aselect_server'         => $result->getMeta('a-select-server'),
            'digid_response_aselect_credentials'    => $result->getMeta('resolveParams.aselect_credentials'),
            'state'                                 => self::STATE_AUTHORIZED,
        ]);
    }

    /**
     * @return ClientTls|null
     */
    protected function getClientCert(): ?ClientTls
    {
        $implementation = $this->implementation;
        $generalImplementation = Implementation::general();

        if ($implementation->digid_cgi_tls_cert && $implementation->digid_cgi_tls_key) {
            return new ClientTls(
                $implementation->digid_cgi_tls_key,
                $implementation->digid_cgi_tls_cert,
            );
        } else if ($generalImplementation->digid_cgi_tls_cert && $generalImplementation->digid_cgi_tls_key) {
            return new ClientTls(
                $generalImplementation->digid_cgi_tls_key,
                $generalImplementation->digid_cgi_tls_cert,
            );
        }

        return null;
    }
}
