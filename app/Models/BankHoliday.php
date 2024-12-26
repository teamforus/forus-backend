<?php

namespace App\Models;


/**
 * App\Models\BankHoliday
 *
 * @property int $id
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankHoliday newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankHoliday newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankHoliday query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankHoliday whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankHoliday whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankHoliday whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|BankHoliday whereUpdatedAt($value)
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
    protected $casts = [
        'date' => 'datetime',
    ];
}