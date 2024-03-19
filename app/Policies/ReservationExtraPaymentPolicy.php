<?php

namespace App\Policies;

use App\Models\Identity;
use App\Models\Organization;
use App\Models\ReservationExtraPayment;
use Illuminate\Auth\Access\HandlesAuthorization;

class ReservationExtraPaymentPolicy
{
    use HandlesAuthorization;
}
