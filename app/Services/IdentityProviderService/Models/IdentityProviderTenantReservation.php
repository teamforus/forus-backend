<?php

namespace App\Services\IdentityProviderService\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $organization_id
 * @property int $connection_id
 * @property string $provider
 * @property string $tenant_id
 * @property \Illuminate\Support\Carbon $first_connected_at
 * @property \Illuminate\Support\Carbon $last_connected_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderTenantReservation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderTenantReservation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderTenantReservation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderTenantReservation whereConnectionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderTenantReservation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderTenantReservation whereFirstConnectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderTenantReservation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderTenantReservation whereLastConnectedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderTenantReservation whereOrganizationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderTenantReservation whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderTenantReservation whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|IdentityProviderTenantReservation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class IdentityProviderTenantReservation extends Model
{
    protected $table = 'identity_provider_tenant_reservations';

    protected $fillable = [
        'organization_id', 'connection_id', 'provider', 'tenant_id', 'first_connected_at', 'last_connected_at',
    ];

    protected $casts = [
        'first_connected_at' => 'datetime',
        'last_connected_at' => 'datetime',
    ];
}
