<?php
/**
 *
 */

namespace App\Models\Traits;

use App\Models\Tag;

/**
 * Trait HasTags
 * @mixin \Eloquent
 * @package App\Models\Traits
 */
trait HasTags
{
    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}