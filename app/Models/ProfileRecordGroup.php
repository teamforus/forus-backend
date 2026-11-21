<?php

namespace App\Models;

use App\Services\TranslationService\Traits\HasTranslationCaches;
use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $key
 * @property string $name
 * @property int $sort_order
 */
class ProfileRecordGroup extends Model
{
    use Translatable;
    use HasTranslationCaches;

    public array $translatedAttributes = ['name'];

    protected $fillable = ['key', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];

    /**
     * @return HasMany
     */
    public function record_types(): HasMany
    {
        return $this->hasMany(RecordType::class);
    }
}
