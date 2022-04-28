<?php

namespace App\Services\DigIdService\Models;

use App\Http\Requests\DigID\StartDigIdRequest;
use App\Models\Fund;
use App\Models\Implementation;
use App\Services\DigIdService\DigIdException;
use App\Services\DigIdService\Repositories\DigIdRepo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * App\Services\DigIdService\Models\DigIdSession
 *
 * @property int $id
 * @property string $state
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
 * @property-read Implementation|null $implementation
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession newQuery()
 * @method static \Illuminate\Database\Query\Builder|DigIdSession onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession query()
 * @method static \Illuminate\Database\Eloquent\Builder|DigIdSession whereClientType($value)
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
    const STATE_CREATED         = 'created';
    // Session expired
    const STATE_EXPIRED         = 'expired';
    // Session created and rid received from digid
    const STATE_PENDING_AUTH    = 'pending_authorization';
    // User authorized session through on digid auth form
    const STATE_AUTHORIZED      = 'authorized';
    // User canceled digid request
    const STATE_CANCELED        = 'canceled';
    // Session has error and can't be used anymore
    const STATE_ERROR           = 'error';

    // List all valid states
    const STATES = [
        self::STATE_CREATED,
        self::STATE_EXPIRED,
        self::STATE_PENDING_AUTH,
        self::STATE_CANCELED,
        self::STATE_AUTHORIZED,
        self::STATE_ERROR,
    ];

    // Sessions which are authorized in 10 minutes are deleted
    const SESSION_EXPIRATION_TIME = 10*60;

    protected $table = 'digid_sessions';

    protected $fillable = [
        'state', 'implementation_id', 'client_type', 'identity_address', 'meta',

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
     * @return DigIdSession|Model
     */
    public static function createSession(StartDigIdRequest $request)
    {
        $token_generator = resolve('token_generator');

        return self::create([
            'client_type'           => $request->client_type(),
            'identity_address'      => $request->auth_address(),
            'implementation_id'     => $request->implementation()->id,
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
     * @return string
     */
    protected function getApiUrl(string $uri): string
    {
        $implementationApiUrl = $this->implementation->digid_forus_api_url;
        $apiHost = $implementationApiUrl ?: url('/');

        return sprintf('%s/%s', rtrim($apiHost, '/'), ltrim($uri, '/'));
    }

    /**
     * @return $this
     */
    public function startAuthSession(): self
    {
        $goBackUrl = $this->getApiUrl(sprintf('/api/v1/platform/digid/%s/resolve', $this->session_uid));

        try {
            $digId = $this->implementation->getDigid();
            $authRequest = $digId->makeAuthRequest(url_extend_get_params($goBackUrl, [
                'session_secret' => $this->session_secret,
            ]));
        } catch (DigIdException $exception) {
            $this->setError($exception->getMessage(), $exception->getDigIdCode());
            return $this;
        }

        // Build redirect URL.
        $digidRedirectUrl = url_extend_get_params($authRequest['as_url'], [
            'rid'               => $authRequest['rid'],
            'a-select-server'   => $authRequest['a-select-server'],
        ]);

        return $this->updateModel([
            'state'                         => self::STATE_PENDING_AUTH,
            'digid_rid'                     => $authRequest['rid'],
            'digid_state'                   => DigIdSession::STATE_PENDING_AUTH,
            'digid_as_url'                  => $authRequest['as_url'],
            'digid_app_url'                 => $goBackUrl,
            'digid_request_aselect_server'  => $authRequest['a-select-server'],
            'digid_auth_redirect_url'       => $digidRedirectUrl
        ]);
    }

    /**
     * @param $message
     * @param $errorCode
     * @return bool
     */
    private function setError($message, $errorCode): bool
    {
        logger()->error(
            sprintf('Could not make digid auth request, got %s: %s', $errorCode, $message)
        );

        $canceled = $errorCode == DigIdRepo::DIGID_CANCELLED;

        return $this->update([
            'digid_error_code'      => $errorCode,
            'digid_error_message'   => DigIdRepo::responseCodeDetails($errorCode),
            'state'                 => $canceled ? self::STATE_CANCELED: self::STATE_ERROR,
        ]);
    }

    /**
     * @param string $rid
     * @param string $aselect_server
     * @param string $aselect_credentials
     * @return bool
     */
    public function requestBsn(
        string $rid,
        string $aselect_server,
        string $aselect_credentials
    ): bool {
        try {
            $result = $this->implementation->getDigid()->getBsnFromResponse(
                $rid, $aselect_server, $aselect_credentials
            );
        } catch (DigIdException $exception) {
            return $this->setError($exception->getMessage(), $exception->getDigIdCode());
        }

        return $this->update([
            'digid_uid'                             => $result['uid'],
            'digid_response_aselect_server'         => $aselect_server,
            'digid_response_aselect_credentials'    => $aselect_credentials,
            'state'                                 => self::STATE_AUTHORIZED,
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
        return sprintf(
            'error%s',
            $this->digid_error_code ? "_" . $this->digid_error_code : ""
        );
    }

    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return $this->getApiUrl(sprintf('/api/v1/platform/digid/%s/redirect', $this->session_uid));
    }

    /**
     * @param StartDigIdRequest $request
     * @return string|null
     */
    protected static function makeFinalRedirectUrl(StartDigIdRequest $request): ?string {
        if ($request->input('request') === 'fund_request') {
            $fund = Fund::find($request->input('fund_id'));

            return $fund->urlWebshop(sprintf('/funds/%s/activate', $fund->id));
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
}
