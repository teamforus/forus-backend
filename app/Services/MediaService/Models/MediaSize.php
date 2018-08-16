<?php

namespace App\Services\MediaService\Models;

use App\Models\Media;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MediaSize
 * @property mixed $id
 * @property integer $media_id
 * @property string $key
 * @property string $path
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @package App\Models
 */
class MediaSize extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'media_id', 'key', 'path'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function media() {
        return $this->belongsTo(Media::class);
    }

    public function unlink() {
        return app()->make('filesystem')->delete($this->path);
    }

    public function urlPublic() {
        return asset('storage/' . ltrim($this->path, "public/"));
    }
}
