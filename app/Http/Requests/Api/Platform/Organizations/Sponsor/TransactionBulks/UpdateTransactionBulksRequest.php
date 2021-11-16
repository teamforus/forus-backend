<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\TransactionBulks;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\VoucherTransactionBulk;
use Illuminate\Support\Facades\Gate;

/**
 * @property-read Organization $organization
 * @property-read VoucherTransactionBulk $voucherTransactionBulk
 */
class UpdateTransactionBulksRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('show', $this->organization);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $states = [
            VoucherTransactionBulk::STATE_PENDING,
        ];

        return [
            'state' => 'required|in:' .  implode(",", $states)
        ];
    }
}
