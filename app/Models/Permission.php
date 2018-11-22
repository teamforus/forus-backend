<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Permission
 * @property mixed $id
 * @property string $key
 * @property string $name
 * @package App\Models
 */
class Permission extends Model
{
    protected $fillable = [
        'key', 'name'
    ];

    public $timestamps = false;
}
