<?php

namespace App\Http\Requests\Api\Platform\Organizations\Vouchers;

use App\Exports\VoucherExport;
use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Models\Voucher;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
class IndexVouchersRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->organization->identityCan($this->identity(), ['manage_vouchers', 'view_vouchers'], false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $funds = $this->organization->funds()->pluck('funds.id');
        $implementations = $this->organization->implementations()->pluck('implementations.id');
        $fields = Arr::pluck(VoucherExport::getExportFields('product'), 'key');

        return [
            'fund_id' => 'nullable|exists:funds,id|in:' . $funds->join(','),
            'granted' => 'nullable|boolean',
            'amount_min' => 'nullable|numeric',
            'amount_max' => 'nullable|numeric',
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
            'type' => 'required|in:fund_voucher,product_voucher,all',
            'unassigned' => 'nullable|boolean',
            'source' => 'required|in:all,user,employee',
            'qr_format' => 'nullable|in:pdf,png,data,all',
            'data_format' => 'nullable|in:csv,xls,all',
            'state' => 'nullable|in:' . implode(',', $this->statesList()),
            'email' => ['nullable', ...$this->emailRules()],
            'bsn' => 'nullable|string|max:100',
            'in_use' => 'nullable|boolean',
            'expired' => 'nullable|boolean',
            'count_per_identity_min' => 'nullable|numeric',
            'count_per_identity_max' => 'nullable|numeric',
            'fields' => 'nullable|array',
            'fields.*' => ['nullable', Rule::in($fields)],
            'identity_address' => 'nullable|exists:identities,address',
            'amount_available_min' => 'nullable|numeric',
            'amount_available_max' => 'nullable|numeric',
            'implementation_id' => 'nullable|exists:implementations,id|in:' . $implementations->join(','),
            // todo: update to default order_by/order_dir format
            'sort_by' => 'nullable|in:amount,expire_at,created_at',
            'sort_order' => 'nullable|in:asc,desc',
            ...$this->sortableResourceRules(),
        ];
    }

    /**
     * @return array
     */
    private function statesList(): array
    {
        return [...Voucher::STATES, 'expired'];
    }
}
