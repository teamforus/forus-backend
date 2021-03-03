<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Request;

/**
 * App\Models\BusinessType
 *
 * @property int $id
 * @property string $key
 * @property int|null $parent_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\Organization[] $organizations
 * @property-read int|null $organizations_count
 * @property-read \App\Models\BusinessTypeTranslation|null $translation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\BusinessTypeTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static Builder|BusinessType listsTranslations(string $translationField)
 * @method static Builder|BusinessType newModelQuery()
 * @method static Builder|BusinessType newQuery()
 * @method static Builder|BusinessType notTranslatedIn(?string $locale = null)
 * @method static Builder|BusinessType orWhereTranslation(string $translationField, $value, ?string $locale = null)
 * @method static Builder|BusinessType orWhereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static Builder|BusinessType orderByTranslation(string $translationField, string $sortMethod = 'asc')
 * @method static Builder|BusinessType query()
 * @method static Builder|BusinessType translated()
 * @method static Builder|BusinessType translatedIn(?string $locale = null)
 * @method static Builder|BusinessType whereCreatedAt($value)
 * @method static Builder|BusinessType whereId($value)
 * @method static Builder|BusinessType whereKey($value)
 * @method static Builder|BusinessType whereParentId($value)
 * @method static Builder|BusinessType whereTranslation(string $translationField, $value, ?string $locale = null, string $method = 'whereHas', string $operator = '=')
 * @method static Builder|BusinessType whereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static Builder|BusinessType whereUpdatedAt($value)
 * @method static Builder|BusinessType withTranslation()
 * @mixin \Eloquent
 */
class BusinessType extends Model
{
    use Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'parent_id'
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatedAttributes = [
        'name'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function organizations(): HasMany {
       return $this->hasMany(Organization::class);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function search(Request $request): Builder {
        /** @var Builder $query */
        $query = self::query();

        if ($request->input('used', false)) {
            $query->whereHas('organizations.supplied_funds_approved', static function(
                Builder $builder
            ) {
                $builder->whereIn(
                    'funds.id',
                    Implementation::activeFunds()->pluck('id')->toArray()
                );
            });
        }

        return $query;
    }
}
