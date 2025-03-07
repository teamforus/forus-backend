<?php

namespace App\Models;

use App\Services\TranslationService\Traits\HasTranslationCaches;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * App\Models\RecordTypeOption.
 *
 * @property int $id
 * @property int $record_type_id
 * @property string $value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\RecordType $record_type
 * @property-read \App\Models\RecordTypeOptionTranslation|null $translation
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\TranslationService\Models\TranslationCache[] $translation_caches
 * @property-read int|null $translation_caches_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Models\RecordTypeOptionTranslation[] $translations
 * @property-read int|null $translations_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption listsTranslations(string $translationField)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption notTranslatedIn(?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption orWhereTranslation(string $translationField, $value, ?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption orWhereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption orderByTranslation(string $translationField, string $sortMethod = 'asc')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption translated()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption translatedIn(?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption whereRecordTypeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption whereTranslation(string $translationField, $value, ?string $locale = null, string $method = 'whereHas', string $operator = '=')
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption whereTranslationLike(string $translationField, $value, ?string $locale = null)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption whereValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecordTypeOption withTranslation(?string $locale = null)
 * @mixin \Eloquent
 */
class RecordTypeOption extends Model
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
     * @var string[]
     */
    protected $fillable = [
        'value',
    ];

    /**
     * @return BelongsTo
     */
    public function record_type(): BelongsTo
    {
        return $this->belongsTo(RecordType::class);
    }
}
