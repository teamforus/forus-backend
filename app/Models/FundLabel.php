<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FundLabel
 *
 * @property mixed $id
 * @property string $name
 * @property Collection|Fund[] $funds
 * @package App\Models
 */
class FundLabel extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'fund_id', 'name'
    ];

    /**\
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function funds() {
        return $this->hasMany(Fund::class);
    }
}
