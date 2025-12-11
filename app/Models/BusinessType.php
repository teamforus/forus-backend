<?php

namespace App\Models;

use App\Models\Traits\Translations\BusinessTypeTranslationTrait;
use App\Scopes\Builders\FundProviderQuery;
use App\Services\TranslationService\Traits\HasTranslationCaches;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

/**
 * App\Models\BusinessType.
 *
 * @property int $id
 * @property string $key
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $organizations
 * @property-read int|null $organizations_count
 * @property-read \App\Models\BusinessTypeTranslation|null $translation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\TranslationService\Models\TranslationCache[] $translation_caches
 * @property-read int|null $translation_caches_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\BusinessTypeTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static Builder<static>|BusinessType listsTranslations(string $translationField)
 * @method static Builder<static>|BusinessType newModelQuery()
 * @method static Builder<static>|BusinessType newQuery()
 * @method static Builder<static>|BusinessType notTranslatedIn(?string $locale = null)
 * @method static Builder<static>|BusinessType orWhereTranslation(string $translationField, $value, ?string $locale = null)
 * @method static Builder<static>|BusinessType orWhereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static Builder<static>|BusinessType orderByTranslation(string $translationField, string $sortMethod = 'asc')
 * @method static Builder<static>|BusinessType query()
 * @method static Builder<static>|BusinessType translated()
 * @method static Builder<static>|BusinessType translatedIn(?string $locale = null)
 * @method static Builder<static>|BusinessType whereCreatedAt($value)
 * @method static Builder<static>|BusinessType whereId($value)
 * @method static Builder<static>|BusinessType whereKey($value)
 * @method static Builder<static>|BusinessType whereParentId($value)
 * @method static Builder<static>|BusinessType whereTranslation(string $translationField, $value, ?string $locale = null, string $method = 'whereHas', string $operator = '=')
 * @method static Builder<static>|BusinessType whereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static Builder<static>|BusinessType whereUpdatedAt($value)
 * @method static Builder<static>|BusinessType withTranslation(?string $locale = null)
 * @mixin \Eloquent
 */
class BusinessType extends BaseModel
{
    use Translatable;
    use BusinessTypeTranslationTrait;
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
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'parent_id',
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function organizations(): HasMany
    {
        return $this->hasMany(Organization::class);
    }

    /**
     * @param Request $request
     * @return Builder|BusinessType
     */
    public static function search(Request $request): Builder|BusinessType
    {
        if ($request->input('used', false)) {
            return self::whereHas('organizations.fund_providers', function (Builder $builder) {
                FundProviderQuery::whereApproved($builder->whereIn('fund_id', Implementation::activeFundsForWebshop()));
            });
        }

        return self::query();
    }
}
