<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Class FundLabel
 *
 * @property mixed $id
 * @property string $name
 * @property string $key
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
        'name', 'key'
    ];

    /**\
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function funds() {
        return $this->hasMany(Fund::class);
    }
}
