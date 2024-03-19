<?php

namespace App\Http\Controllers\Api\Platform\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Resources\PhysicalCardResource;
use App\Models\PhysicalCard;
use App\Models\VoucherToken;
use App\Http\Requests\Api\Platform\Vouchers\PhysicalCards\StorePhysicalCardRequest;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\Response;

/**
 * Class PhysicalCardsController
 * @package App\Http\Controllers\Api\Platform\Vouchers
 */
class PhysicalCardsController extends Controller
{
    use ThrottleWithMeta;

    private $maxAttempts = 5;
    private $decayMinutes = 60 * 24;
}
