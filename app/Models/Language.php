<?php

namespace App\Models;

use Exception;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

/**
 * App\Models\Language
 *
 * @property int $id
 * @property string $locale
 * @property string $name
 * @property bool $base
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Collection|\App\Models\ImplementationLanguage[] $implementation_languages
 * @property-read int|null $implementation_languages_count
 * @property-read Collection|\App\Models\Implementation[] $implementations
 * @property-read int|null $implementations_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language whereBase($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language whereLocale($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Language whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Language extends BaseModel
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'locale', 'name', 'base'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'base' => 'boolean',
    ];

    /**
     * Define the relationship with the ImplementationLanguage model.
     *
     * @return HasMany
     * @noinspection PhpUnused
     */
    public function implementation_languages(): HasMany
    {
        return $this->hasMany(ImplementationLanguage::class);
    }

    /**
     * Define a relation to the implementations through the pivot table.
     *
     * @return BelongsToMany
     * @noinspection PhpUnused
     */
    public function implementations(): BelongsToMany
    {
        return $this->belongsToMany(
            Implementation::class,
            'implementation_languages',
            'language_id',
            'implementation_id',
        );
    }

    /**
     * @param array $fallback
     * @return array|string[]
     */
    public static function getSupportedLocales(array $fallback = []): array
    {
        try {
            return self::pluck('locale')->toArray();
        } catch (Exception) {
            return $fallback;
        }
    }

    /**
     * @return Collection|self[]
     */
    public static function getAllLanguages(): Collection|Arrayable
    {
        return Cache::driver('array')->remember('languages-all', 0, fn () => self::get());
    }
}
