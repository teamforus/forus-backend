<?php

namespace App\Models;

use App\Services\MediaService\Traits\HasMedia;
use App\Traits\HasMarkdownDescription;
use Illuminate\Database\Eloquent\Builder;

/**
 * App\Models\Faq
 *
 * @property int $id
 * @property string $title
 * @property string $description
 * @property int|null $faq_id
 * @property string|null $faq_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $description_html
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MediaService\Models\Media[] $medias
 * @property-read int|null $medias_count
 * @method static Builder|Faq newModelQuery()
 * @method static Builder|Faq newQuery()
 * @method static Builder|Faq query()
 * @method static Builder|Faq whereCreatedAt($value)
 * @method static Builder|Faq whereId($value)
 * @method static Builder|Faq whereTitle($value)
 * @method static Builder|Faq whereDescription($value)
 * @method static Builder|Faq whereFaqId($value)
 * @method static Builder|Faq whereFaqType($value)
 * @method static Builder|Faq whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Faq extends BaseModel
{
    use HasMarkdownDescription, HasMedia;

    protected $table = 'faq';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'faq_id', 'faq_type', 'title', 'description',
    ];
}
