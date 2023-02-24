<?php

namespace App\Models;

use App\Models\Traits\Translations\RecordTranslationsTrait;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;

/**
 * App\Models\RecordType
 *
 * @property int $id
 * @property string $key
 * @property string $type
 * @property bool $system
 * @property int $vouchers
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
 * @method static Builder|RecordType whereVouchers($value)
 * @method static Builder|RecordType withTranslation()
 * @mixin \Eloquent
 */
class RecordType extends BaseModel
{
    use Translatable, RecordTranslationsTrait;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'type', 'system', 'vouchers',
    ];

    protected $perPage = 100;

    /**
     * The attributes that are translatable.
     *
     * @var array
     * @noinspection PhpUnused
     */
    public array $translatedAttributes = [
        'name',
    ];

    protected $casts = [
        'system' => 'bool',
    ];

    /**
     * @param bool $withSystem
     * @param array $filters
     * @return RecordType|Builder
     */
    public static function searchQuery(bool $withSystem = true, array $filters = []): RecordType|Builder
    {
        /** @var RecordType $query */
        $query = static::where(fn(Builder $builder) => $builder->where($withSystem ? [] : [
            'system' => false,
        ]))->with('translations');

        if (Arr::get($filters, 'vouchers', false)) {
            $query->where('vouchers', true);
        }

        return $query;
    }

    /**
     * @param bool $withSystem
     * @param array $filters
     * @return Collection|RecordType
     */
    public static function search(bool $withSystem = true, array $filters = []): Collection|array
    {
        return static::searchQuery($withSystem, $filters)->get();
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
