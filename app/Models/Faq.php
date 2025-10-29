<?php

namespace App\Models;

use App\Services\MediaService\Traits\HasMedia;
use App\Services\TranslationService\Traits\HasOnDemandTranslations;
use App\Traits\HasMarkdownDescription;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\Faq.
 *
 * @property int $id
 * @property int $faq_id
 * @property string $faq_type
 * @property string $type
 * @property string $title
 * @property string|null $subtitle
 * @property string $description
 * @property int $order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $description_html
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MediaService\Models\Media[] $medias
 * @property-read int|null $medias_count
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\TranslationService\Models\TranslationValue[] $translation_values
 * @property-read int|null $translation_values_count
 * @method static Builder<static>|Faq newModelQuery()
 * @method static Builder<static>|Faq newQuery()
 * @method static Builder<static>|Faq query()
 * @method static Builder<static>|Faq whereCreatedAt($value)
 * @method static Builder<static>|Faq whereDescription($value)
 * @method static Builder<static>|Faq whereFaqId($value)
 * @method static Builder<static>|Faq whereFaqType($value)
 * @method static Builder<static>|Faq whereId($value)
 * @method static Builder<static>|Faq whereOrder($value)
 * @method static Builder<static>|Faq whereSubtitle($value)
 * @method static Builder<static>|Faq whereTitle($value)
 * @method static Builder<static>|Faq whereType($value)
 * @method static Builder<static>|Faq whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Faq extends Model
{
    use HasMedia;
    use HasMarkdownDescription;
    use HasOnDemandTranslations;

    public const string TYPE_QUESTION = 'question';
    public const string TYPE_TITLE = 'title';

    protected $table = 'faq';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'faq_id', 'faq_type', 'title', 'subtitle', 'description', 'order', 'type',
    ];
}
