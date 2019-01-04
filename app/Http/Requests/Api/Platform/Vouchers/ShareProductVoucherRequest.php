<?php

namespace App\Http\Requests\Api\Platform\Vouchers;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Class ShareProductVoucherRequest
 * @package App\Http\Requests\Api\Platform\Vouchers
 */
class ShareProductVoucherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'reason' => 'required'
        ];
    }
}
