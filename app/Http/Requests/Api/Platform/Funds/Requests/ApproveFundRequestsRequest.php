<?php

namespace App\Http\Requests\Api\Platform\Funds\Requests;

use App\Http\Requests\BaseFormRequest;
use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Organization;
use Exception;
use Illuminate\Validation\Rule;

/**
 * @property FundRequest $fund_request
 */
class ApproveFundRequestsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        if (!$this->supportsChangingAmount($this->fund_request->fund) &&
            ($this->input('amount') || $this->input('fund_amount_preset_id'))) {
            return false;
        }

        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'note' => 'nullable|string|between:0,2000',
            'amount' => $this->amountRules($this->fund_request->fund),
            'fund_amount_preset_id' => $this->amountPresetRules($this->fund_request->fund),
        ];
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function amountRules(Fund $fund): array
    {
        return
            $this->supportsChangingAmount($fund) &&
            $fund->organization->allow_payouts &&
            $fund->fund_config->allow_custom_amounts_validator ? [
            $this->hasFormula($fund) ? 'nullable' : (
                $fund->organization->allow_payouts && $fund->fund_config->allow_preset_amounts_validator ?
                    'required_without:fund_amount_preset_id' :
                    'required'
            ),
            'numeric',
            'min:' . currency_format($fund->fund_config->custom_amount_min),
            'max:' . currency_format($fund->fund_config->custom_amount_max),
        ] : [
            'nullable',
            'in:',
        ];
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function amountPresetRules(Fund $fund): array
    {
        return
            $this->supportsChangingAmount($fund) &&
            $fund->organization->allow_payouts &&
            $fund->fund_config->allow_preset_amounts_validator ? [
            $this->hasFormula($fund) ? 'nullable' : (
            $fund->organization->allow_payouts && $fund->fund_config->allow_custom_amounts_validator ?
                'required_without:amount' :
                'required'
            ),
            Rule::exists('fund_amount_presets', 'id')->where('fund_id', $fund->id),
        ] : [
            'nullable',
            'in:',
        ];
    }

    /**
     * @param Fund $fund
     * @return int
     */
    protected function hasFormula(Fund $fund): int
    {
        return $fund->fund_formulas->count() + $fund->formula_products->count();
    }

    /**
     * @param Fund $fund
     * @return int
     */
    protected function supportsChangingAmount(Fund $fund): int
    {
        return in_array($fund->organization->fund_request_resolve_policy, [
            Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
            Organization::FUND_REQUEST_POLICY_AUTO_AVAILABLE,
        ]);
    }

    /**
     * @return void
     * @throws Exception
     */
    protected function failedAuthorization(): void
    {
        throw new Exception("Dit fonds ondersteunt geen wijziging van bedragen.", 403);
    }
}
