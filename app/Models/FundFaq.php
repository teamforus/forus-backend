<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * App\Models\FundFaq
 *
 * @property int $id
 * @property int $fund_id
 * @property string $title
 * @property string $description
 */
class FundFaq extends Model
{
    protected $table = 'fund_faq';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'title', 'description'
    ];
}
