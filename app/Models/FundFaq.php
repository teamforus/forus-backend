<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FundFaq
 *
 * @property int $id
 * @property int $fund_id
 * @property string $title
 * @property-read string $description_html
 * @property string $description
 */
class FundFaq extends Model
{
    protected $table = 'fund_faq';

    protected $appends = ['description_html'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'title', 'description'
    ];

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getDescriptionHtmlAttribute(): string
    {
        return resolve('markdown')->convertToHtml($this->description ?? '');
    }
}
