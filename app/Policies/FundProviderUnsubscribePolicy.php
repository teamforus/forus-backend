<?php

namespace App\Policies;

use App\Models\FundProviderUnsubscribe;
use App\Models\Identity;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;

class FundProviderUnsubscribePolicy
{
    use HandlesAuthorization;
}
