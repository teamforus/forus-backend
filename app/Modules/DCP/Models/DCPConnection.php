<?php

namespace App\Modules\DCP\Models;

use App\Models\Implementation;
use App\Models\Organization;
use App\Modules\DCP\Enums\DCPEnvironment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;

/**
 * @property int $id
 * @property int $implementation_id
 * @property DCPEnvironment $environment
 * @property bool $enabled
 * @property string|null $username
 * @property string|null $password
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Implementation $implementation
 */
class DCPConnection extends Model
{
    protected $table = 'dcp_connections';

    protected $fillable = ['implementation_id', 'environment', 'enabled', 'username', 'password'];

    protected $hidden = ['username', 'password'];

    protected $casts = [
        'environment' => DCPEnvironment::class,
        'enabled' => 'boolean',
        'password' => 'encrypted',
    ];

    /**
     * @return BelongsTo
     */
    public function implementation(): BelongsTo
    {
        return $this->belongsTo(Implementation::class);
    }

    /**
     * @return HasMany
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'dcp_connection_id');
    }

    /**
     * @return HasMany
     */
    public function dcp_orders(): HasMany
    {
        return $this->hasMany(DCPOrder::class, 'dcp_connection_id');
    }

    /**
     * @return string|null
     */
    public function endpoint(): ?string
    {
        return Config::get('dcp.environments.' . $this->environment->value);
    }

    /**
     * @return bool
     */
    public function canSend(): bool
    {
        return
            Config::get('dcp.enabled') && $this->enabled && $this->endpoint() && $this->username && $this->password;
    }
}
