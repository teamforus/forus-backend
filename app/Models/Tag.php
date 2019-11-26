<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class FundLabel
 *
 * @property mixed $id
 * @property string $name
 * @property string $key
 * @package App\Models
 */
class Tag extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'key'
    ];
}