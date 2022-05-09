<?php

namespace App\Models;

use App\Traits\HasMarkdownDescription;

/**
 * App\Models\FundFaq
 *
 * @property-read string $description_html
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq query()
 * @mixin \Eloquent
 */
class FundFaq extends Model
{
    use HasMarkdownDescription;

    protected $table = 'fund_faq';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'title', 'description',
    ];
}
