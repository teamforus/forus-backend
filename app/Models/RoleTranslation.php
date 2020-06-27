<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class RoleTranslation
 * @package App\Models
 */
class RoleTranslation extends Model
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function role() {
        return $this->belongsTo(Role::class);
    }
}
