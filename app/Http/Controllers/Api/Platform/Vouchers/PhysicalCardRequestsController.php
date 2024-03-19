<?php

namespace App\Http\Controllers\Api\Platform\Vouchers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Vouchers\PhysicalCardRequests\StorePhysicalCardRequestRequest;
use App\Http\Resources\PhysicalCardRequestResource;
use App\Models\PhysicalCardRequest;
use App\Models\VoucherToken;
use App\Traits\ThrottleWithMeta;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Class PhysicalCardRequestsController
 * @package App\Http\Controllers\Api\Platform\Vouchers
 */
class PhysicalCardRequestsController extends Controller
{
    use ThrottleWithMeta;

    private $maxAttempts = 3;
    private $decayMinutes = 60 * 24;
}
