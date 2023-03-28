<?php

namespace App\Models;


/**
 * App\Models\BankHoliday
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|BankHoliday newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankHoliday newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|BankHoliday query()
 * @method static \Illuminate\Database\Eloquent\Builder|BankHoliday whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankHoliday whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankHoliday whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|BankHoliday whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class BankHoliday extends BaseModel
{
    /**
     * @var string[]
     */
    protected $fillable = [
        'date',
    ];

    /**
     * @var string[]
     */
    protected $dates = [
        'date',
    ];
}