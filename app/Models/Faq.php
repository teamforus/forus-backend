<?php

namespace App\Models;

use App\Services\MediaService\Traits\HasMedia;
use App\Services\TranslationService\Traits\HasOnDemandTranslations;
use App\Traits\HasMarkdownDescription;
use Illuminate\Database\Eloquent\Builder;

/**
 * App\Models\Faq
 *
 * @property int $id
 * @property int $faq_id
 * @property string $faq_type
 * @property string $title
 * @property string $description
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $description_html
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MediaService\Models\Media[] $medias
 * @property-read int|null $medias_count
 * @method static Builder<static>|Faq newModelQuery()
 * @method static Builder<static>|Faq newQuery()
 * @method static Builder<static>|Faq query()
 * @method static Builder<static>|Faq whereCreatedAt($value)
 * @method static Builder<static>|Faq whereDescription($value)
 * @method static Builder<static>|Faq whereFaqId($value)
 * @method static Builder<static>|Faq whereFaqType($value)
 * @method static Builder<static>|Faq whereId($value)
 * @method static Builder<static>|Faq whereOrder($value)
 * @method static Builder<static>|Faq whereTitle($value)
 * @method static Builder<static>|Faq whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Faq extends BaseModel
{
    use HasMedia;
    use HasMarkdownDescription;
    use HasOnDemandTranslations;

    protected $table = 'faq';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'faq_id', 'faq_type', 'title', 'description', 'order',
    ];
}
