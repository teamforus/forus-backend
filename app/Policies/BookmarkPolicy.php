<?php

namespace App\Policies;

use App\Models\Identity;
use Illuminate\Auth\Access\HandlesAuthorization;

class BookmarkPolicy
{
    use HandlesAuthorization;

    /**
     * @param Identity|null $identity
     * @return bool
     * @noinspection PhpUnused
     */
    public function setBookmark(?Identity $identity): bool
    {
        return $identity->exists;
    }

    /**
     * @param Identity|null $identity
     * @return bool
     * @noinspection PhpUnused
     */
    public function removeBookmark(?Identity $identity): bool
    {
        return $this->setBookmark($identity);
    }
}
