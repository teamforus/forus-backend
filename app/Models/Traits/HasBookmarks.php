<?php

namespace App\Models\Traits;

use App\Models\Bookmark;
use App\Models\Identity;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin \Eloquent
 */
trait HasBookmarks
{
    /**
     * @return MorphMany
     */
    public function bookmarks(): MorphMany
    {
        return $this->morphMany(Bookmark::class, 'bookmarkable');
    }

    /**
     * @param Identity $identity
     * @return static
     */
    public function addBookmark(Identity $identity): static
    {
        $this->bookmarks()->firstOrCreate([
            'identity_address' => $identity->address,
        ]);

        return $this;
    }

    /**
     * @param Identity $identity
     * @return Product|HasBookmarks
     */
    public function removeBookmark(Identity $identity): self
    {
        $this->bookmarks()->where([
            'identity_address' => $identity->address,
        ])->delete();

        return $this;
    }
}
