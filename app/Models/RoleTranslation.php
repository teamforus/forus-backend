<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class RoleTranslation
 *
 * @package App\Models
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $role_id
 * @property string $locale
 * @property-read \App\Models\Role $role
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RoleTranslation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RoleTranslation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RoleTranslation query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RoleTranslation whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RoleTranslation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RoleTranslation whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RoleTranslation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\RoleTranslation whereRoleId($value)
 * @mixin \Eloquent
 */
class RoleTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name', 'description', 'locale'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role(): BelongsTo {
        return $this->belongsTo(Role::class);
    }
}
