<?php

namespace App\Models;

use App\Traits\HasMarkdownDescription;

/**
 * App\Models\FundFaq
 *
 * @property int $id
 * @property int $fund_id
 * @property string $title
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string $description_html
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq whereFundId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundFaq whereUpdatedAt($value)
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