<?php

namespace App\Services\Forus\Record\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Services\MediaService\Models\Media;

/**
 * Class RecordCategory
 * @property mixed $id
 * @property string $identity_address
 * @property string $name
 * @property integer $order
 * @property Media $icon
 * @property Collection $records
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class RecordCategory extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'identity_address', 'name', 'order'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function records()
    {
        return $this->hasMany(Record::class);
    }

    /**
     * Get category icon
     * @return MorphOne
     */
    public function icon()
    {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'record_category_icon'
        ]);
    }
};
