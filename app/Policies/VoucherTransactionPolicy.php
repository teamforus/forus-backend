<?php

namespace App\Policies;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use Illuminate\Auth\Access\HandlesAuthorization;

class VoucherTransactionPolicy
{
    use HandlesAuthorization;
}
