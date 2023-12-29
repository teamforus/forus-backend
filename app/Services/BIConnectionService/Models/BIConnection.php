<?php

namespace App\Services\BIConnectionService\Models;

use App\Models\Organization;
use App\Models\Traits\HasDbTokens;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

/**
 * App\Services\BIConnectionService\Models\BIConnection
 *
 * @property int $id
 * @property int $organization_id
 * @property string $auth_type
 * @property string $access_token
 * @property string $expiration_period
 * @property array|null $data_types
 * @property array|null $ips
 * @property \Illuminate\Support\Carbon $expire_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Organization $organization
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection query()
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereAuthType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereDataTypes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereExpirationPeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereExpireAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereIps($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BIConnection extends Model
{
    use HasLogs, HasDbTokens;

    protected $table = 'bi_connections';

    public const AUTH_TYPE_HEADER = 'header';
    public const AUTH_TYPE_DISABLED = 'disabled';
    public const AUTH_TYPE_PARAMETER = 'parameter';

    public const AUTH_TYPE_HEADER_NAME = 'X-API-KEY';
    public const AUTH_TYPE_PARAMETER_NAME = 'api_key';

    public const AUTH_TYPES = [
        self::AUTH_TYPE_HEADER,
        self::AUTH_TYPE_DISABLED,
        self::AUTH_TYPE_PARAMETER,
    ];

    public const EXPIRATION_PERIODS = [
        '24_hour',
        '1_week',
        '1_month',
    ];

    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'replaced';

    public const DATA_TYPES = [
        'vouchers' => 'Tegoeden',
        'fund_identities' => 'Aanvragers',
        'reimbursements' => 'Declaraties',
        'employees' => 'Medewerkers',
        'funds' => 'Financieel overzicht uitgaven',
        'funds_detailed' => 'Financieel overzicht tegoeden',
        'fund_providers' => 'Aanbieders',
        'fund_provider_finances' => 'Aanbieder transacties',
        'voucher_transactions' => 'Transacties vanuit tegoeden',
        'voucher_transaction_bulks' => 'Bulk transacties vanuit tegoeden',
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'auth_type', 'access_token', 'expiration_period', 'data_types', 'ips', 'organization_id',
        'expire_at',
    ];

    /**
     * @var string[]
     */
    protected $hidden = [
        'access_token',
    ];

    /**
     * @var string[]
     */
    protected $casts = [
        'ips' => 'array',
        'data_types' => 'array',
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'expire_at',
    ];

    /**
     * @return BelongsTo
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return string
     */
    public static function makeToken(): string
    {
        return token_generator()->generate(64);
    }

    /**
     * @param Organization $organization
     * @param Request $request
     * @return BIConnection
     */
    public static function makeConnection(Organization $organization, Request $request): BIConnection
    {
        $expirationUnits = explode('_', $request->get('expiration_period'));

        /** @var BIConnection $connection */
        $connection = $organization->bi_connection()->create([
            ...$request->only('auth_type', 'expiration_period', 'data_types', 'ips'),
            'access_token' => BIConnection::makeToken(),
            'expire_at' => now()->addUnit($expirationUnits[1], $expirationUnits[0]),
        ]);

        $connection->log(self::EVENT_CREATED, [
            'bi_connection' => $connection,
        ], [
            'access_token' => $connection->access_token,
            'access_token_previous' => null,
        ]);

        return $connection;
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function change(Request $request): static
    {
        $auth_type = $request->get('auth_type');

        $this->update($auth_type === self::AUTH_TYPE_DISABLED
            ? compact('auth_type')
            : $request->only('auth_type', 'expiration_period', 'data_types', 'ips')
        );

        $this->log(self::EVENT_UPDATED, [
            'bi_connection' => $this,
        ]);

        return $this;
    }

    /**
     * @return $this
     */
    public function resetToken(): static
    {
        if ($this->auth_type !== self::AUTH_TYPE_DISABLED) {
            $connectionToken = $this->access_token;
            $expirationUnits = explode('_', $this->expiration_period);

            $this->update([
                'access_token' => BIConnection::makeToken(),
                'expire_at' => now()->addUnit($expirationUnits[1], $expirationUnits[0]),
            ]);

            $this->log(self::EVENT_UPDATED, [
                'bi_connection' => $this,
            ], [
                'access_token' => $this->access_token,
                'access_token_previous' => $connectionToken,
            ]);
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function tokenExpired(): bool
    {
        return $this->expire_at->isPast();
    }
}
