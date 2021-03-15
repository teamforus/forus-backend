<?php

namespace App\Services\Forus\Record\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Services\Forus\Record\Models\RecordType
 *
 * @property int $id
 * @property string $key
 * @property string $type
 * @property bool $system
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\Forus\Record\Models\RecordTypeTranslation|null $translation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\Forus\Record\Models\RecordTypeTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType listsTranslations(string $translationField)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType notTranslatedIn(?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType orWhereTranslation(string $translationField, $value, ?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType orWhereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType orderByTranslation(string $translationField, string $sortMethod = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType query()
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType translated()
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType translatedIn(?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType whereSystem($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType whereTranslation(string $translationField, $value, ?string $locale = null, string $method = 'whereHas', string $operator = '=')
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType whereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RecordType withTranslation()
 * @mixin \Eloquent
 */
class RecordType extends Model
{
    use Translatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'key', 'type', 'system',
    ];

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatedAttributes = [
        'name'
    ];

    protected $casts = [
        'system' => 'bool',
    ];

    /**
     * @param array $attributes
     * @param array $options
     * @return bool|\App\Models\Model
     */
    public function updateModel(array $attributes = [], array $options = [])
    {
        return tap($this)->update($attributes, $options);
    }
}
