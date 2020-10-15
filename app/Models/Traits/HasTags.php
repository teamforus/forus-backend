<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Relations\MorphToMany;
use App\Models\Tag;

/**
 * Trait HasTags
 * @package App\Models\Traits
 */
trait HasTags
{
    /**
     * @return MorphToMany
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }
}