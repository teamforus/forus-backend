<?php

namespace App\Http\Requests\Api\Platform\Vouchers;

use App\Models\VoucherToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;

/**
 * Class ShareProductVoucherRequest
 * @property-read VoucherToken $voucher_token_address
 * @package App\Http\Requests\Api\Platform\Vouchers
 */
class DeactivateVoucherRequest extends FormRequest
{

}
