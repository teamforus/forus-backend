<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class PhysicalCard
 * @property integer $id
 * @property integer $voucher_id
 * @property string $physical_card_code
 * @property Voucher $voucher
 * @package App\Models
 */
class PhysicalCard extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'voucher_id', 'physical_card_code'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function voucher() {
        return $this->belongsTo(Voucher::class);
    }
}
