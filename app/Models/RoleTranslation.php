<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\RoleTranslation.
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $role_id
 * @property string $locale
 * @property-read \App\Models\Role $role
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslation whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslation whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RoleTranslation whereRoleId($value)
 * @mixin \Eloquent
 */
class RoleTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name', 'description', 'locale',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
