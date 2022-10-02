<?php

namespace App\Models;

use App\Services\MediaService\Traits\HasMedia;
use App\Traits\HasMarkdownDescription;

/**
 * App\Models\ImplementationPageFaq
 *
 * @property int $id
 * @property int $implementation_page_id
 * @property string $title
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $description_html
 * @property-read \Illuminate\Database\Eloquent\Collection|\App\Services\MediaService\Models\Media[] $medias
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq whereImplementationPageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ImplementationPageFaq extends BaseModel
{
    use HasMedia, HasMarkdownDescription;

    protected $table = 'implementation_page_faq';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'implementation_page_id', 'title', 'description',
    ];
}