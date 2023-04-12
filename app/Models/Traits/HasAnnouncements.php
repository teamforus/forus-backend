<?php

namespace App\Models\Traits;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin \Eloquent
 */
trait HasAnnouncements
{
    /**
     * @return MorphMany
     */
    public function announcements(): MorphMany
    {
        return $this->morphMany(Announcement::class, 'announceable');
    }
}