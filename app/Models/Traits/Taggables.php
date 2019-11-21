<?php
/**
 *
 */

namespace App\Models\Traits;

trait Taggables
{
    public function tags()
    {
        return $this->morphToMany('App\Models\Tag', 'taggable');
    }
}