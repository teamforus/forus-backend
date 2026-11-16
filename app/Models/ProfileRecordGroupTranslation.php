<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $profile_record_group_id
 * @property string $locale
 * @property string $name
 */
class ProfileRecordGroupTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['name'];

    /**
     * @return BelongsTo
     */
    public function profile_record_group(): BelongsTo
    {
        return $this->belongsTo(ProfileRecordGroup::class);
    }
}
