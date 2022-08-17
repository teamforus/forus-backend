<?php

namespace App\Models;

use App\Models\Traits\RecordTranslationTrait;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * App\Models\RecordType
 *
 * @property int $id
 * @property string $key
 * @property string $type
 * @property bool $system
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\RecordTypeTranslation|null $translation
 * @property-read Collection|\App\Models\RecordTypeTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static Builder|RecordType listsTranslations(string $translationField)
 * @method static Builder|RecordType newModelQuery()
 * @method static Builder|RecordType newQuery()
 * @method static Builder|RecordType notTranslatedIn(?string $locale = null)
 * @method static Builder|RecordType orWhereTranslation(string $translationField, $value, ?string $locale = null)
 * @method static Builder|RecordType orWhereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static Builder|RecordType orderByTranslation(string $translationField, string $sortMethod = 'asc')
 * @method static Builder|RecordType query()
 * @method static Builder|RecordType translated()
 * @method static Builder|RecordType translatedIn(?string $locale = null)
 * @method static Builder|RecordType whereCreatedAt($value)
 * @method static Builder|RecordType whereId($value)
 * @method static Builder|RecordType whereKey($value)
 * @method static Builder|RecordType whereSystem($value)
 * @method static Builder|RecordType whereTranslation(string $translationField, $value, ?string $locale = null, string $method = 'whereHas', string $operator = '=')
 * @method static Builder|RecordType whereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static Builder|RecordType whereType($value)
 * @method static Builder|RecordType whereUpdatedAt($value)
 * @method static Builder|RecordType withTranslation()
 * @mixin \Eloquent
 */
class RecordType extends BaseModel
{
    use Translatable, RecordTranslationTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'type', 'system',
    ];

    protected $perPage = 100;

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public array $translatedAttributes = [
        'name',
    ];

    protected $casts = [
        'system' => 'bool',
    ];

    /**
     * @param bool $withSystem
     * @return RecordType|Builder
     */
    public static function searchQuery(bool $withSystem = true): RecordType|Builder
    {
        return static::where(fn(Builder $builder) => $builder->where($withSystem ? [] : [
            'system' => false,
        ]));
    }

    /**
     * @param bool $withSystem
     * @return Collection|RecordType[]
     */
    public static function search(bool $withSystem = true): Collection|array
    {
        return static::searchQuery($withSystem)->get();
    }

    /**
     * @param string $key
     * @return RecordType|null
     */
    public static function findByKey(string $key): ?RecordType
    {
        return static::where('key', $key)->first();
    }
}
