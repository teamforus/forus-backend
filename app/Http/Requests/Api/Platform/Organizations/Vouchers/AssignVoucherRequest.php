<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignVoucherRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $bsn = $this->input('bsn');

        return [
            'email' => 'required_without:bsn|email:strict,dns',
            'bsn' => [
                'required_without:email',
                'string',
                'size:9',
                ($bsn && !record_repo()->identityAddressByBsn($bsn)) ? Rule::in([]) : null,
            ],
        ];
    }
}
