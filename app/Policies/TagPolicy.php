<?php

namespace App\Policies;

use App\Models\Tag;
use Illuminate\Auth\Access\HandlesAuthorization;

class TagPolicy
{
    use HandlesAuthorization;

    /**
     * @param string|null $identity_address
     * @return bool
     */
    public function viewAny(?string $identity_address): bool
    {
        return true;
    }

    /**
     * @param string|null $identity_address
     * @param Tag $tag
     * @return bool
     */
    public function show(?string $identity_address, Tag $tag): bool
    {
        return true;
    }
}
