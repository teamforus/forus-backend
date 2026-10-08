<?php

namespace App\Services\IdentityProviderService\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uid
 * @property int $connection_id
 * @property string $token_hash
 * @property int $created_by_identity_id
 * @property int|null $revoked_by_identity_id
 * @property Carbon|null $revoked_at
 * @property Carbon|null $last_used_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read IdentityProviderConnection $connection
 * @method static Builder<static>|IdentityProviderScimCredential newModelQuery()
 * @method static Builder<static>|IdentityProviderScimCredential newQuery()
 * @method static Builder<static>|IdentityProviderScimCredential query()
 * @mixin \Eloquent
 */
class IdentityProviderScimCredential extends Model
{
    protected $table = 'identity_provider_scim_credentials';

    protected $fillable = [
        'uid', 'connection_id', 'token_hash', 'created_by_identity_id', 'revoked_by_identity_id',
        'revoked_at', 'last_used_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'revoked_at' => 'datetime',
        'last_used_at' => 'datetime',
    ];

    /**
     * @return BelongsTo
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(IdentityProviderConnection::class, 'connection_id');
    }

    /**
     * @return bool
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (IdentityProviderScimCredential $credential): void {
            $credential->uid ??= Str::uuid()->toString();
        });
    }
}
