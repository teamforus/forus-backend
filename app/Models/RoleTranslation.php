<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RoleTranslation
 * @package App\Models
 */
class RoleTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name', 'description', 'locale'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role() {
        return $this->belongsTo(Role::class);
    }
}
