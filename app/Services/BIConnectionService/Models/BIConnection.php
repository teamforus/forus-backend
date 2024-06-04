<?php

namespace App\Services\BIConnectionService\Models;

use App\Helpers\Arr;
use App\Models\Organization;
use App\Models\Traits\HasDbTokens;
use App\Services\EventLogService\Traits\HasLogs;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Services\BIConnectionService\Models\BIConnection
 *
 * @property int $id
 * @property int $organization_id
 * @property bool $enabled
 * @property string $access_token
 * @property int $expiration_period
 * @property array|null $data_types
 * @property array|null $ips
 * @property Carbon $expire_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\EventLogService\Models\EventLog[] $logs
 * @property-read int|null $logs_count
 * @property-read Organization $organization
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection query()
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereAccessToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereDataTypes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BIConnection whereEnabled($value)
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

    public const AUTH_TYPE_HEADER_NAME = 'X-API-KEY';

    public const EXPIRATION_PERIODS = [1, 7, 30];

    public const EVENT_CREATED = 'created';
    public const EVENT_UPDATED = 'replaced';

    /**
     * @var string[]
     */
    protected $fillable = [
        'enabled', 'access_token', 'expiration_period', 'data_types', 'ips', 'organization_id',
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
        'enabled' => 'boolean',
        'data_types' => 'array',
        'expiration_period' => 'integer',
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
     * @param array $config
     * @return BIConnection
     */
    public static function createConnection(Organization $organization, array $config): BIConnection
    {
        /** @var BIConnection $connection */
        $connection = $organization->bi_connection()->create([
            ...Arr::only($config, ['enabled', 'expiration_period', 'data_types', 'ips']),
            'access_token' => BIConnection::makeToken(),
            'expire_at' => Carbon::now()->addDays(Arr::get($config, 'expiration_period', 0)),
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
     * @param array $config
     * @return $this
     */
    public function updateConnection(array $config): BIConnection
    {
        $enable = Arr::get($config, 'enabled');

        $this->update($enable ? Arr::only($config, [
            'enabled', 'expiration_period', 'data_types', 'ips',
        ]): [
            'enabled' => false,
        ]);

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
        $connectionToken = $this->access_token;

        $this->update([
            'access_token' => BIConnection::makeToken(),
            'expire_at' => Carbon::now()->addDays($this->expiration_period),
        ]);

        $this->log(self::EVENT_UPDATED, [
            'bi_connection' => $this,
        ], [
            'access_token' => $this->access_token,
            'access_token_previous' => $connectionToken,
        ]);

        return $this;
    }

    /**
     * @return bool
     */
    public function isExpired(): bool
    {
        return $this->expire_at->isPast();
    }
}
