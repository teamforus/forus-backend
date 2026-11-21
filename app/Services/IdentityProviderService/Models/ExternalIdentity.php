<?php

namespace App\Services\IdentityProviderService\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $uid
 * @property string $provider
 * @property string $tenant_id
 * @property string $object_id
 * @property string|null $issuer
 * @property string|null $subject
 * @property int|null $identity_id
 * @property \Illuminate\Support\Carbon|null $last_authenticated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|IdentityProviderMembership[] $memberships
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalIdentity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalIdentity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalIdentity query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalIdentity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalIdentity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalIdentity whereIdentityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalIdentity whereIssuer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalIdentity whereLastAuthenticatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalIdentity whereObjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalIdentity whereProvider($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalIdentity whereSubject($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalIdentity whereTenantId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalIdentity whereUid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ExternalIdentity whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ExternalIdentity extends Model
{
    protected $table = 'external_identities';

    protected $fillable = [
        'uid', 'provider', 'tenant_id', 'object_id', 'issuer', 'subject',
        'identity_id', 'last_authenticated_at',
    ];

    protected $casts = [
        'last_authenticated_at' => 'datetime',
    ];

    /**
     * @return HasMany
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(IdentityProviderMembership::class, 'external_identity_id');
    }

    /**
     * @return void
     */
    protected static function booted(): void
    {
        static::creating(function (ExternalIdentity $identity): void {
            $identity->uid ??= Str::uuid()->toString();
        });
    }
}
