<?php

namespace App\Services\DigIdService\Models;

use App\Models\Implementation;
use App\Services\DigIdService\DigIdException;
use App\Services\DigIdService\Repositories\DigIdRepo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


/**
 * App\Services\DigIdService\Models\DigIdSession
 *
 * @property int $id
 * @property string $state
 * @property int|null $implementation_id
 * @property string|null $client_type
 * @property string|null $identity_address
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
 * @property-read \App\Models\Implementation|null $implementation
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession newQuery()
 * @method static \Illuminate\Database\Query\Builder|\App\Services\DigIdService\Models\DigIdSession onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereClientType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereDigidAppUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereDigidAsUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereDigidAuthRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereDigidErrorCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereDigidErrorMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereDigidRequestAselectServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereDigidResponseAselectCredentials($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereDigidResponseAselectServer($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereDigidRid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereDigidUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereSessionFinalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereSessionRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereSessionSecret($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereSessionUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\DigIdService\Models\DigIdSession whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\App\Services\DigIdService\Models\DigIdSession withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\App\Services\DigIdService\Models\DigIdSession withoutTrashed()
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
        'state', 'implementation_id', 'client_type', 'identity_address',

        'session_uid', 'session_secret', 'session_final_url',
        'session_request',

        'digid_rid', 'digid_uid', 'digid_app_url', 'digid_as_url',
        'digid_auth_redirect_url', 'digid_error_code',
        'digid_error_message', 'digid_request_aselect_server',
        'digid_response_aselect_server', 'digid_response_aselect_credentials',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function implementation() {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @param string|null $identity_address
     * @param Implementation $implementation
     * @param string $client_type
     * @param string $finalRedirectUrl
     * @param string $requestType
     * @return DigIdSession|Model
     */
    public static function createSession(
        ?string $identity_address,
        Implementation $implementation,
        string $client_type,
        string $finalRedirectUrl,
        string $requestType
    ) {
        $token_generator = resolve('token_generator');

        return self::create([
            'client_type'            => $client_type,
            'identity_address'      => $identity_address,
            'implementation_id'     => $implementation->id,
            'state'                 => DigIdSession::STATE_CREATED,

            'session_uid'           => $token_generator->generate(100),
            'session_secret'        => $token_generator->generate(200),
            'session_final_url'     => $finalRedirectUrl,
            'session_request'       => $requestType,
        ]);
    }

    /**
     * @param string $goBackUrl
     * @return bool|mixed
     */
    public function startAuthSession(string $goBackUrl) {
        try {
            $digId = $this->implementation->getDigid();
            $authRequest = $digId->makeAuthRequest(url_extend_get_params($goBackUrl, [
                'session_secret' => $this->session_secret,
            ]));
        } catch (DigIdException $exception) {
            return $this->setError($exception->getMessage(), $exception->getDigIdCode());
        }

        // Build redirect URL.
        $digidRedirectUrl = url_extend_get_params($authRequest['as_url'], [
            'rid'               => $authRequest['rid'],
            'a-select-server'   => $authRequest['a-select-server'],
        ]);

        return tap($this->update([
            'state'                         => self::STATE_PENDING_AUTH,
            'digid_rid'                     => $authRequest['rid'],
            'digid_state'                   => DigIdSession::STATE_PENDING_AUTH,
            'digid_as_url'                  => $authRequest['as_url'],
            'digid_app_url'                 => $goBackUrl,
            'digid_request_aselect_server'  => $authRequest['a-select-server'],
            'digid_auth_redirect_url'       => $digidRedirectUrl
        ]));
    }

    /**
     * @param $message
     * @param $errorCode
     * @return bool
     */
    private function setError($message, $errorCode) {
        logger()->error(sprintf(
            'Could not make digid auth request, got %s: %s',
            $errorCode,
            $message
        ));

        $canceled = $errorCode == DigIdRepo::DIGID_CANCELLED;

        $this->update([
            'digid_error_code'      => $errorCode,
            'digid_error_message'   => DigIdRepo::responseCodeDetails($errorCode),
            'state'                 => $canceled ? self::STATE_CANCELED: self::STATE_ERROR,
        ]);

        return false;
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
    ) {
        try {
            $result = $this->implementation->getDigid()->getBsnFromResponse(
                $rid, $aselect_server, $aselect_credentials
            );
        } catch (DigIdException $exception) {
            return $this->setError(
                $exception->getMessage(), $exception->getDigIdCode());
        }

        return tap($this)->update([
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
}
