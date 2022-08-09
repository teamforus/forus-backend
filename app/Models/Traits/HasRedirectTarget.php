<?php

namespace App\Models\Traits;

use App\Models\Redirect;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin \Eloquent
 */
trait HasRedirectTarget
{
    /**
     * @return MorphOne
     */
    public function redirect(): MorphOne
    {
        return $this->morphOne(Redirect::class, 'redirectable');
    }
}