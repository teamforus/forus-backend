<?php

namespace App\Services\IdentityProviderService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $identity_proxy_id
 * @property int $connection_id
 * @property int $membership_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\IdentityProviderService\Models\IdentityProviderConnection $connection
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderProxyBinding newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderProxyBinding newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderProxyBinding query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderProxyBinding whereConnectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderProxyBinding whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderProxyBinding whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderProxyBinding whereIdentityProxyId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderProxyBinding whereMembershipId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderProxyBinding whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class IdentityProviderProxyBinding extends Model
{
    protected $table = 'identity_provider_proxy_bindings';

    protected $fillable = [
        'identity_proxy_id', 'connection_id', 'membership_id',
    ];

    /**
     * @return BelongsTo
     */
    public function connection(): BelongsTo
    {
        return $this->belongsTo(IdentityProviderConnection::class);
    }
}
