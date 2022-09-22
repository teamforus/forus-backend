<?php

namespace App\Models\Traits;

use App\Models\Bookmark;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Trait HasBookmark
 */
trait HasBookmark
{
    /**
     * @return MorphOne
     */
    public function bookmark(): MorphOne
    {
        return $this->morphOne(Bookmark::class, 'bookmarkable');
    }
}