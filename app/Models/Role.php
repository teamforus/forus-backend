<?php

namespace App\Models;

use App\Models\Traits\Translations\RoleTranslationsTrait;
use App\Services\TranslationService\Traits\HasTranslationCaches;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * App\Models\Role.
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
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\TranslationService\Models\TranslationCache[] $translation_caches
 * @property-read int|null $translation_caches_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\RoleTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role listsTranslations(string $translationField)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role notTranslatedIn(?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role orWhereTranslation(string $translationField, $value, ?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role orWhereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role orderByTranslation(string $translationField, string $sortMethod = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role translated()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role translatedIn(?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereTranslation(string $translationField, $value, ?string $locale = null, string $method = 'whereHas', string $operator = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role whereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Role withTranslation(?string $locale = null)
 * @mixin \Eloquent
 */
class Role extends Model
{
    use Translatable;
    use RoleTranslationsTrait;
    use HasTranslationCaches;

    public $timestamps = false;

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public array $translatedAttributes = [
        'name', 'description',
    ];

    protected $fillable = [
        'key',
    ];

    /**
     * @return BelongsToMany
     */
    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, (new EmployeeRole())->getTable());
    }

    /**
     * @return BelongsToMany
     */
    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, (new RolePermission())->getTable());
    }

    /**
     * @return HasMany
     */
    public function role_permissions(): HasMany
    {
        return $this->hasMany(RolePermission::class);
    }

    /**
     * @param array|string $permission
     * @return void
     * @noinspection PhpUnused
     */
    public function attachPermissions(array|string $permission): void
    {
        $this->permissions()->syncWithoutDetaching(
            Permission::whereIn('key', (array) $permission)->pluck('id')->toArray(),
        );
    }

    /**
     * @param string $key
     * @return static|null
     */
    public static function byKey(string $key): ?Role
    {
        return self::where([
            'key' => $key,
        ])->first();
    }
}
