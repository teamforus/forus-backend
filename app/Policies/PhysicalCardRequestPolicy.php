<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Voucher;
use App\Models\PhysicalCardRequest;
use Illuminate\Auth\Access\HandlesAuthorization;

class PhysicalCardRequestPolicy
{
    use HandlesAuthorization;
}
