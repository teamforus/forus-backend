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
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Services\Forus\Record\Models\RecordTypeTranslation $translation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\Forus\Record\Models\RecordTypeTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType listsTranslations($translationField)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType notTranslatedIn($locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType orWhereTranslation($translationField, $value, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType orWhereTranslationLike($translationField, $value, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType orderByTranslation($translationField, $sortMethod = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType translated()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType translatedIn($locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType whereKey($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType whereTranslation($translationField, $value, $locale = null, $method = 'whereHas', $operator = '=')
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType whereTranslationLike($translationField, $value, $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Services\Forus\Record\Models\RecordType withTranslation()
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
        'key', 'type'
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
     * @param array $attributes
     * @param array $options
     * @return bool|\App\Models\Model
     */
    public function updateModel(array $attributes = [], array $options = [])
    {
        return tap($this)->update($attributes, $options);
    }
}
