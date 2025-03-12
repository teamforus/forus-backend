<?php

namespace App\Models;

use App\Services\TranslationService\Traits\HasTranslationCaches;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * App\Models\Tag.
 *
 * @property int $id
 * @property string $key
 * @property string $scope
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Fund[] $funds
 * @property-read int|null $funds_count
 * @property-read \App\Models\TagTranslation|null $translation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\TranslationService\Models\TranslationCache[] $translation_caches
 * @property-read int|null $translation_caches_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\TagTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag listsTranslations(string $translationField)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag notTranslatedIn(?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag orWhereTranslation(string $translationField, $value, ?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag orWhereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag orderByTranslation(string $translationField, string $sortMethod = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag translated()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag translatedIn(?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereScope($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereTranslation(string $translationField, $value, ?string $locale = null, string $method = 'whereHas', string $operator = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag withTranslation(?string $locale = null)
 * @mixin \Eloquent
 */
class Tag extends BaseModel
{
    use Translatable;
    use HasTranslationCaches;

    /**
     * The attributes that are translatable.
     *
     * @var array
     * @noinspection PhpUnused
     */
    public array $translatedAttributes = [
        'name',
    ];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'scope', 'fund_id',
    ];

    /**
     * @var int
     */
    protected $perPage = 100;

    /**
     * Get all funds with the tag.
     *
     * @return MorphToMany
     */
    public function funds(): MorphToMany
    {
        return $this->morphedByMany(Fund::class, 'taggable');
    }
}
