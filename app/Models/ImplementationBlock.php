<?php

namespace App\Models;

use App\Services\MediaService\Models\Media;
use App\Services\MediaService\Traits\HasMedia;
use App\Traits\HasMarkdownDescription;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * App\Models\ImplementationBlock
 *
 * @property int $implementation_page_id
 * @property string $label
 * @property string $title
 * @property string $description
 * @property bool $button_enabled
 * @property string $button_text
 * @property string $button_link
 * @property string $description_html
 * @property Media|null $photo
 */
class ImplementationBlock extends Model
{
    use HasFactory, HasMedia, HasMarkdownDescription;

    const TYPE_TEXT = 'text';
    const TYPE_DETAILED = 'detailed';

    const TYPES = [
        self::TYPE_TEXT,
        self::TYPE_DETAILED,
    ];

    /**
     * @var string[]
     */
    protected $fillable = [
        'implementation_page_id', 'key', 'type', 'label', 'title', 'description',
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
            'type' => 'implementation_block_media'
        ]);
    }
}
