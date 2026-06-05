<?php

namespace App\Services\OpenIdService\Models;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

/**
 * @property int $id
 * @property string $provider
 * @property int $implementation_id
 * @property string $client_type
 * @property string|null $identity_address
 * @property string $session_uid
 * @property string $session_final_url
 * @property string $openid_auth_redirect_url
 * @property string $session_request
 * @property string $session_state
 * @property string $state
 * @property string $nonce
 * @property string|null $code_verifier
 * @property string|null $target
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Identity|null $identity
 * @property-read Implementation $implementation
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereClientType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereCodeVerifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereNonce($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereResolvedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereSessionFinalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereOpenidAuthRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereSessionRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereSessionState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereSessionUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereTarget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OpenIdSession withoutTrashed()
 * @mixin \Eloquent
 */
class OpenIdSession extends Model
{
    use SoftDeletes;

    public const string STATE_PENDING = 'pending';
    public const string STATE_RESOLVED = 'resolved';
    public const string STATE_EXPIRED = 'expired';
    public const string STATE_ERROR = 'error';
    public const int SESSION_EXPIRATION_TIME = 10 * 60;
    public const int SESSION_RETENTION_TIME = 30 * 24 * 60 * 60;
    public const string REQUEST_AUTH = 'auth';
    public const string REQUEST_FUND_REQUEST = 'fund_request';

    public const array TERMINAL_STATES = [
        self::STATE_RESOLVED,
        self::STATE_EXPIRED,
        self::STATE_ERROR,
    ];

    public const array REQUEST_TYPES = [
        self::REQUEST_AUTH,
        self::REQUEST_FUND_REQUEST,
    ];

    protected $table = 'openid_sessions';

    /**
     * @var string[]
     */
    protected $casts = [
        'meta' => 'array',
        'resolved_at' => 'datetime',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'provider',
        'implementation_id',
        'client_type',
        'identity_address',
        'session_uid',
        'session_final_url',
        'openid_auth_redirect_url',
        'session_request',
        'session_state',
        'state',
        'nonce',
        'code_verifier',
        'target',
        'meta',
        'resolved_at',
    ];

    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @param Implementation $implementation
     * @param string $clientType
     * @param string|null $target
     * @param string $provider
     * @param array $authorization
     * @param string $sessionRequest
     * @param Fund|null $fund
     * @param string|null $identityAddress
     * @return OpenIdSession
     */
    public static function createSession(
        Implementation $implementation,
        string $clientType,
        ?string $target,
        string $provider,
        array $authorization,
        string $sessionRequest = self::REQUEST_AUTH,
        ?Fund $fund = null,
        ?string $identityAddress = null
    ): OpenIdSession {
        return self::create([
            'provider' => $provider,
            'implementation_id' => $implementation->id,
            'client_type' => $clientType,
            'identity_address' => $identityAddress,
            'session_uid' => token_generator()->generate(100),
            'session_final_url' => self::makeFinalRedirectUrl($implementation, $clientType, $sessionRequest, $fund),
            'openid_auth_redirect_url' => $authorization['redirect_url'],
            'session_request' => $sessionRequest,
            'session_state' => self::STATE_PENDING,
            'state' => $authorization['state'],
            'nonce' => $authorization['nonce'],
            'code_verifier' => $authorization['code_verifier'],
            'target' => $sessionRequest === self::REQUEST_AUTH ? $target : null,
            'meta' => self::makeSessionMeta(
                $sessionRequest,
                $fund,
                is_array($authorization['meta'] ?? null) ? $authorization['meta'] : []
            ),
        ]);
    }

    /**
     * @return bool
     */
    public function isPending(): bool
    {
        return $this->session_state === self::STATE_PENDING;
    }

    /**
     * @return string
     */
    public function getRedirectUrl(): string
    {
        return url(sprintf('/api/v1/platform/openid/%s/redirect', $this->session_uid));
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return !$this->created_at || $this->created_at->lt(Carbon::now()->subSeconds(self::SESSION_EXPIRATION_TIME));
    }

    /**
     * @return bool
     */
    public function markResolved(): bool
    {
        return $this->update([
            'session_state' => self::STATE_RESOLVED,
            'resolved_at' => Carbon::now(),
        ]);
    }

    /**
     * @return bool
     */
    public function markExpired(): bool
    {
        return $this->update([
            'session_state' => self::STATE_EXPIRED,
        ]);
    }

    /**
     * @return bool
     */
    public function markError(): bool
    {
        return $this->update([
            'session_state' => self::STATE_ERROR,
        ]);
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
     * @return Organization|null
     */
    public function sessionOrganization(): ?Organization
    {
        $fund = Fund::find($this->meta['fund_id'] ?? null);

        return $fund?->organization ?: $this->implementation?->organization;
    }

    /**
     * @param Identity $identity
     * @return Model|OpenIdSession
     */
    public function setIdentity(Identity $identity): Model|OpenIdSession
    {
        $this->update([
            'identity_address' => $identity->address,
        ]);

        return $this->unsetRelation('identity');
    }

    /**
     * @param Implementation $implementation
     * @param string $clientType
     * @param string $sessionRequest
     * @param Fund|null $fund
     * @return string
     */
    protected static function makeFinalRedirectUrl(
        Implementation $implementation,
        string $clientType,
        string $sessionRequest,
        ?Fund $fund = null
    ): string {
        if ($sessionRequest === self::REQUEST_FUND_REQUEST) {
            if (!$fund) {
                throw new InvalidArgumentException('OpenID fund request session requires a fund.');
            }

            return $fund->urlWebshop(sprintf('/fondsen/%s/activeer', $fund->id));
        }

        return $implementation->urlFrontend($clientType);
    }

    /**
     * @param string $sessionRequest
     * @param Fund|null $fund
     * @param array $authorizationMeta
     * @return array
     */
    protected static function makeSessionMeta(
        string $sessionRequest,
        ?Fund $fund = null,
        array $authorizationMeta = []
    ): array {
        $authorizationMeta = array_filter($authorizationMeta, static fn ($value) => $value !== null);

        if ($sessionRequest === self::REQUEST_FUND_REQUEST) {
            return array_merge($authorizationMeta, [
                'fund_id' => $fund?->id,
            ]);
        }

        return $authorizationMeta;
    }
}
