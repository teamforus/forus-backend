<?php

namespace App\Models;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * App\Models\ImplementationBlock
 * @property Media|null photo
 */
class ImplementationBlock extends Model
{
    use HasFactory, HasMedia;

    /**
     * @var string[]
     */
    protected $fillable = [
        'implementation_page_id', 'label', 'title', 'description',
        'button_enabled', 'button_text', 'button_link'
    ];

    /**
     * Get fund banner
     * @return MorphOne
     * @noinspection PhpUnused
     */
    public function photo(): MorphOne
    {
        return $this->morphOne(Media::class, 'mediable')->where([
            'type' => 'cms_media'
        ]);
    }
}
