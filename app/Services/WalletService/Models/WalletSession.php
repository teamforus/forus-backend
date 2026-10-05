<?php

namespace App\Services\WalletService\Models;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Config;
use InvalidArgumentException;

/**
 * @property int $id
 * @property int $wallet_flow_id
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
 * @property string|null $browser_token_hash
 * @property string|null $target
 * @property array<array-key, mixed>|null $meta
 * @property \Illuminate\Support\Carbon|null $resolved_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read Identity|null $identity
 * @property-read Implementation $implementation
 * @property-read WalletFlow|null $wallet_flow
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereClientType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereCodeVerifier($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereIdentityAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereImplementationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereMeta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereNonce($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereWalletFlowId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereResolvedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereSessionFinalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereOpenidAuthRedirectUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereSessionRequest($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereSessionState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereSessionUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereTarget($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession withTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|WalletSession withoutTrashed()
 * @mixin \Eloquent
 */
class WalletSession extends Model
{
    use SoftDeletes;

    public const string STATE_PENDING = 'pending';
    public const string STATE_RESOLVED = 'resolved';
    public const string STATE_EXPIRED = 'expired';
    public const string STATE_ERROR = 'error';
    public const string REQUEST_AUTH = 'auth';
    public const string REQUEST_FUND_REQUEST = 'fund_request';
    public const string REQUEST_DISCLOSURE = 'disclosure';

    public const array TERMINAL_STATES = [
        self::STATE_RESOLVED,
        self::STATE_EXPIRED,
        self::STATE_ERROR,
    ];

    public const array REQUEST_TYPES = [
        self::REQUEST_AUTH,
        self::REQUEST_FUND_REQUEST,
    ];

    protected $table = 'wallet_sessions';

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
        'wallet_flow_id', 'implementation_id', 'client_type', 'identity_address', 'session_uid', 'session_final_url',
        'openid_auth_redirect_url', 'session_request', 'session_state', 'state', 'nonce', 'code_verifier', 'target',
        'browser_token_hash', 'meta', 'resolved_at',
    ];

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
    public function wallet_flow(): BelongsTo
    {
        return $this->belongsTo(WalletFlow::class, 'wallet_flow_id');
    }

    /**
     * @return BelongsTo
     */
    public function identity(): BelongsTo
    {
        return $this->belongsTo(Identity::class, 'identity_address', 'address');
    }

    /**
     * @param Implementation $implementation
     * @param WalletFlow $flow
     * @param string $clientType
     * @param string|null $target
     * @param array $authorization
     * @param string $sessionRequest
     * @param Fund|null $fund
     * @param string|null $identityAddress
     * @param string|null $browserToken
     * @return WalletSession
     */
    public static function createSession(
        Implementation $implementation,
        WalletFlow $flow,
        string $clientType,
        ?string $target,
        array $authorization,
        string $sessionRequest = self::REQUEST_AUTH,
        ?Fund $fund = null,
        ?string $identityAddress = null,
        ?string $browserToken = null
    ): WalletSession {
        return self::create([
            'browser_token_hash' => $browserToken ? hash('sha256', $browserToken) : null,
            'wallet_flow_id' => $flow->id,
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
        return url(sprintf('/api/v1/platform/wallets/%s/redirect', $this->session_uid));
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return !$this->created_at ||
            $this->created_at->lt(Carbon::now()->subSeconds(Config::get('openid.session_expiration_seconds')));
    }

    /**
     * @return bool
     */
    public function markResolved(): bool
    {
        return $this->update([
            'session_state' => self::STATE_RESOLVED,
            'browser_token_hash' => null,
            'code_verifier' => null,
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
            'browser_token_hash' => null,
            'code_verifier' => null,
        ]);
    }

    /**
     * @return bool
     */
    public function markError(): bool
    {
        return $this->update([
            'session_state' => self::STATE_ERROR,
            'browser_token_hash' => null,
            'code_verifier' => null,
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
     * @return Organization|null
     */
    public function sessionOrganization(): ?Organization
    {
        $fund = Fund::find($this->meta['fund_id'] ?? null);

        return $fund?->organization ?: $this->implementation?->organization;
    }

    /**
     * @param Identity $identity
     * @return Model|WalletSession
     */
    public function setIdentity(Identity $identity): Model|WalletSession
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
        if ($sessionRequest === self::REQUEST_DISCLOSURE) {
            return $implementation->urlWebshop($fund ? "/fondsen/$fund->id/activeer" : '/gegevens');
        }

        if ($sessionRequest === self::REQUEST_FUND_REQUEST) {
            if (!$fund) {
                throw new InvalidArgumentException('Wallet fund request session requires a fund.');
            }

            return $fund->urlWebshop(sprintf('/fondsen/%s/activeer', $fund->id));
        }

        return $implementation->urlFrontend($clientType);
    }

    /**
     * @param Fund|null $fund
     * @param array $authorizationMeta
     * @return array
     */
    protected static function makeSessionMeta(
        ?Fund $fund = null,
        array $authorizationMeta = []
    ): array {
        $authorizationMeta = array_filter($authorizationMeta, static fn ($value) => $value !== null);

        if ($fund) {
            return array_merge($authorizationMeta, [
                'fund_id' => $fund->id,
            ]);
        }

        return $authorizationMeta;
    }
}
