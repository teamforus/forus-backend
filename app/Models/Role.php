<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Role
 *
 * @property int $id
 * @property string $key
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Employee[] $employees
 * @property-read int|null $employees_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Permission[] $permissions
 * @property-read int|null $permissions_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\RolePermission[] $role_permissions
 * @property-read int|null $role_permissions_count
 * @property-read \App\Models\RoleTranslation|null $translation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\RoleTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role listsTranslations($translationField)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role notTranslatedIn($locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role orWhereTranslation($translationField, $value, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role orWhereTranslationLike($translationField, $value, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role orderByTranslation($translationField, $sortMethod = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role translated()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role translatedIn($locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role whereTranslation($translationField, $value, $locale = null, $method = 'whereHas', $operator = '=')
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role whereTranslationLike($translationField, $value, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Role withTranslation()
 * @mixin \Eloquent
 */
class Role extends Model
{
    use Translatable;

    protected $fillable = [
        'key'
    ];

    public $timestamps = false;

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatedAttributes = [
        'name', 'description'
    ];

    public function employees(): BelongsToMany {
        return $this->belongsToMany(Employee::class, (
        new EmployeeRole
        )->getTable());
    }

    public function permissions(): BelongsToMany {
        return $this->belongsToMany(Permission::class, (
            new RolePermission
        )->getTable());
    }

    public function role_permissions(): HasMany {
        return $this->hasMany(RolePermission::class);
    }
}
