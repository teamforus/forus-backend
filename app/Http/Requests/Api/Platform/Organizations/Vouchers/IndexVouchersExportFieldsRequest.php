<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * Class IndexVouchersRequest
 * @property-read Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Vouchers
 */
class IndexVouchersExportFieldsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->organization->identityCan($this->identity(), 'manage_vouchers');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'type' => 'nullable|in:budget,product',
        ];
    }
}
