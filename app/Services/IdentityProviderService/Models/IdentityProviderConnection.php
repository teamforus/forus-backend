<?php

namespace App\Services\IdentityProviderService\Models;

use App\Models\Identity;
use App\Models\Organization;
use App\Services\EventLogService\Models\EventLog;
use App\Services\EventLogService\Traits\HasLogs;
use App\Services\IdentityProviderService\Queries\IdentityProviderMembershipQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uid
 * @property int $organization_id
 * @property-read int|null $current_organization_id
 * @property-read string|null $current_tenant_id
 * @property string $provider
 * @property string $tenant_id
 * @property string $issuer
 * @property string $status
 * @property int $created_by_identity_id
 * @property int $updated_by_identity_id
 * @property \Illuminate\Support\Carbon|null $consented_at
 * @property \Illuminate\Support\Carbon|null $enabled_at
 * @property \Illuminate\Support\Carbon|null $paused_at
 * @property \Illuminate\Support\Carbon|null $disconnected_at
 * @property \Illuminate\Support\Carbon|null $last_auth_success_at
 * @property \Illuminate\Support\Carbon|null $last_auth_failure_at
 * @property string|null $last_auth_failure_code
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection|EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Collection|IdentityProviderMembership[] $memberships
 * @property-read int|null $memberships_count
 * @property-read Collection|IdentityProviderMembership[] $memberships_current
 * @property-read int|null $memberships_current_count
 * @property-read Collection|IdentityProviderMembership[] $memberships_current_claimed
 * @property-read int|null $memberships_current_claimed_count
 * @property-read Collection|IdentityProviderMembership[] $memberships_requesters
 * @property-read int|null $memberships_requesters_count
 * @property-read Collection|IdentityProviderScimCredential[] $scim_credentials
 * @property-read int|null $scim_credentials_count
 * @property-read IdentityProviderScimCredential|null $latest_scim_credential
 * @property-read Organization $organization
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereConsentedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereCreatedByIdentityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereDisconnectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereEnabledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereIssuer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereLastAuthFailureAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereLastAuthFailureCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereLastAuthSuccessAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection wherePausedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderConnection whereUpdatedByIdentityId($value)
 * @mixin \Eloquent
 */
class IdentityProviderConnection extends Model
{
    use HasLogs;

    public const string PROVIDER_ENTRA = 'entra';

    public const string STATUS_ENABLED = 'enabled';
    public const string STATUS_PAUSED = 'paused';
    public const string STATUS_DISCONNECTED = 'disconnected';

    public const string EVENT_CONNECTION_CONNECTED = 'connection_connected';
    public const string EVENT_CONNECTION_PAUSED = 'connection_paused';
    public const string EVENT_CONNECTION_RESUMED = 'connection_resumed';
    public const string EVENT_CONNECTION_DISCONNECTED = 'connection_disconnected';
    public const string EVENT_SCIM_CREDENTIAL_CREATED = 'scim_credential_created';
    public const string EVENT_SCIM_CREDENTIAL_ROTATED = 'scim_credential_rotated';
    public const string EVENT_SCIM_CREDENTIAL_REVOKED = 'scim_credential_revoked';
    public const string EVENT_SCIM_USER_CREATED = 'scim_user_created';
    public const string EVENT_SCIM_USER_REPROVISIONED = 'scim_user_reprovisioned';
    public const string EVENT_SCIM_USER_REACTIVATED = 'scim_user_reactivated';
    public const string EVENT_SCIM_USER_DISABLED = 'scim_user_disabled';
    public const string EVENT_SCIM_USER_DELETED = 'scim_user_deleted';
    public const string EVENT_SCIM_USER_UPDATED = 'scim_user_updated';
    public const string EVENT_SCIM_USER_EMAIL_CHANGED = 'scim_user_email_changed';
    public const string EVENT_SCIM_USER_CREATE_FAILED = 'scim_user_create_failed';
    public const string EVENT_SCIM_USER_UPDATE_FAILED = 'scim_user_update_failed';
    public const string EVENT_SCIM_USER_DELETE_FAILED = 'scim_user_delete_failed';
    public const string EVENT_ENTRA_LINK_SELF_LINKED = 'entra_link_self_linked';
    public const string EVENT_ENTRA_LINK_SELF_UNLINKED = 'entra_link_self_unlinked';
    public const string EVENT_MEMBERSHIP_RETIRED_BY_DISCONNECTION = 'membership_retired_by_disconnection';
    public const string EVENT_ENTRA_LOGIN_SUCCEEDED = 'entra_login_succeeded';
    public const string EVENT_ENTRA_LOGIN_FAILED = 'entra_login_failed';

    public const string EVENT_OUTCOME_SUCCESS = 'success';
    public const string EVENT_OUTCOME_FAILURE = 'failure';

    public const array EVENTS_SSO = [
        self::EVENT_CONNECTION_CONNECTED,
        self::EVENT_CONNECTION_PAUSED,
        self::EVENT_CONNECTION_RESUMED,
        self::EVENT_CONNECTION_DISCONNECTED,
        self::EVENT_ENTRA_LINK_SELF_LINKED,
        self::EVENT_ENTRA_LINK_SELF_UNLINKED,
        self::EVENT_MEMBERSHIP_RETIRED_BY_DISCONNECTION,
        self::EVENT_ENTRA_LOGIN_SUCCEEDED,
        self::EVENT_ENTRA_LOGIN_FAILED,
    ];

    public const array EVENTS_REQUESTER_PROVISIONING = [
        self::EVENT_SCIM_CREDENTIAL_CREATED,
        self::EVENT_SCIM_CREDENTIAL_ROTATED,
        self::EVENT_SCIM_CREDENTIAL_REVOKED,
        self::EVENT_SCIM_USER_CREATED,
        self::EVENT_SCIM_USER_REPROVISIONED,
        self::EVENT_SCIM_USER_REACTIVATED,
        self::EVENT_SCIM_USER_DISABLED,
        self::EVENT_SCIM_USER_DELETED,
        self::EVENT_SCIM_USER_UPDATED,
        self::EVENT_SCIM_USER_EMAIL_CHANGED,
        self::EVENT_SCIM_USER_CREATE_FAILED,
        self::EVENT_SCIM_USER_UPDATE_FAILED,
        self::EVENT_SCIM_USER_DELETE_FAILED,
    ];

    protected $table = 'identity_provider_connections';

    protected $fillable = [
        'uid', 'organization_id', 'provider', 'tenant_id', 'issuer', 'status',
        'created_by_identity_id', 'updated_by_identity_id', 'consented_at', 'enabled_at', 'paused_at', 'disconnected_at',
        'last_auth_success_at', 'last_auth_failure_at', 'last_auth_failure_code',
    ];

    protected $casts = [
        'consented_at' => 'datetime',
        'enabled_at' => 'datetime',
        'paused_at' => 'datetime',
        'disconnected_at' => 'datetime',
        'last_auth_success_at' => 'datetime',
        'last_auth_failure_at' => 'datetime',
    ];

    /**
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(IdentityProviderMembership::class, 'connection_id');
    }

    /**
     * @return HasMany
     */
    public function memberships_current(): HasMany
    {
        return $this->memberships()->where(fn (Builder $q) => IdentityProviderMembershipQuery::whereCurrent($q));
    }

    /**
     * @return HasMany
     */
    public function memberships_current_claimed(): HasMany
    {
        return $this->memberships_current()->where(fn (Builder $q) => IdentityProviderMembershipQuery::whereClaimed($q));
    }

    /**
     * @return HasMany
     */
    public function memberships_requesters(): HasMany
    {
        return $this->memberships()->where('account_type', IdentityProviderMembership::ACCOUNT_TYPE_REQUESTER);
    }

    /**
     * @return HasMany
     */
    public function scim_credentials(): HasMany
    {
        return $this->hasMany(IdentityProviderScimCredential::class, 'connection_id');
    }

    /**
     * @return HasOne
     */
    public function latest_scim_credential(): HasOne
    {
        return $this->hasOne(IdentityProviderScimCredential::class, 'connection_id')->latestOfMany();
    }

    /**
     * @param string $path
     * @return string
     */
    public function scimUrl(string $path = ''): string
    {
        return URL::to(sprintf('/api/v1/scim/%s/v2%s', $this->uid, $path));
    }

    /**
     * @return bool
     */
    public function isEntra(): bool
    {
        return $this->provider === self::PROVIDER_ENTRA;
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->status === self::STATUS_ENABLED;
    }

    /**
     * @return bool
     */
    public function isPaused(): bool
    {
        return $this->status === self::STATUS_PAUSED;
    }

    /**
     * @return bool
     */
    public function isDisconnected(): bool
    {
        return $this->status === self::STATUS_DISCONNECTED;
    }

    /**
     * @param string $eventType
     * @param string $outcome
     * @param ?IdentityProviderMembership $membership
     * @param ?string $errorCode
     * @param ?Identity $identity
     * @param array $context
     * @return EventLog
     */
    public function recordEvent(
        string $eventType,
        string $outcome = self::EVENT_OUTCOME_SUCCESS,
        ?IdentityProviderMembership $membership = null,
        ?string $errorCode = null,
        ?Identity $identity = null,
        array $context = [],
    ): EventLog {
        if ($membership?->isRequester() && in_array($eventType, self::EVENTS_REQUESTER_PROVISIONING, true)) {
            $context = [...$this->scimAccountContext($membership), ...$context];
        }

        return $this->log(
            $eventType,
            raw_meta: [
                'membership_id' => $membership?->id,
                'outcome' => $outcome,
                'error_code' => $errorCode,
                'context' => $context ?: null,
            ],
            identity_address: $identity?->address,
            useRequestIdentity: false,
        );
    }

    /**
     * @param IdentityProviderMembership $membership
     * @return array
     */
    public function scimAccountContext(IdentityProviderMembership $membership): array
    {
        return [
            'account_email' => $membership->identity->email,
            'external_id' => $membership->external_identity->object_id,
            'identity_id' => $membership->identity_id,
            'profile_id' => $membership->identity->profiles()->where('organization_id', $this->organization_id)->value('id'),
        ];
    }

    /**
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (IdentityProviderConnection $connection): void {
            $connection->uid ??= Str::uuid()->toString();
        });
    }
}
