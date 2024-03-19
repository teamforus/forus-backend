<?php

namespace App\Http\Requests\Api\Platform\Organizations\ProductReservations;

use App\Exceptions\AuthorizationJsonException;
use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use Illuminate\Support\Facades\Config;

/**
 * @property Organization $organization
 */
class FetchExtraPaymentProductReservationsRequest extends BaseFormRequest
{

}
