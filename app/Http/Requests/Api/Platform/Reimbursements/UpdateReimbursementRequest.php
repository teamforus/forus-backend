<?php

namespace App\Http\Requests\Api\Platform\Reimbursements;

use App\Models\Reimbursement;
use App\Rules\Base\IbanRule;
use App\Rules\FileUidRule;
use Illuminate\Support\Facades\Gate;

/**
 * @property Reimbursement $reimbursement
 */
class UpdateReimbursementRequest extends StoreReimbursementRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return Gate::allows('update', [
            $this->reimbursement,
            $this->identityProxy2FAConfirmed(),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => 'nullable|string|max:200',
            'description' => 'nullable|string|min:5|max:2000',
            'amount' => 'nullable|' . $this->amountRule($this->identity(), $this->reimbursement),
            'email' => 'nullable|string|max:200',
            'iban' => ['nullable', 'string', new IbanRule()],
            'iban_name' => 'nullable|string|min:5|max:200',
            'voucher_id' => $this->voucherIdRule($this->reimbursement),
            'state' => 'nullable|in:' . implode(',', [
                Reimbursement::STATE_DRAFT,
                Reimbursement::STATE_PENDING,
            ]),
            'files' => 'required|array',
            'files.*' => ['required', 'string', new FileUidRule('reimbursement_proof', $this->reimbursement)],
        ];
    }
}
