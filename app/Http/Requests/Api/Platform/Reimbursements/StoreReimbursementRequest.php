<?php

namespace App\Http\Requests\Api\Platform\Reimbursements;

use App\Http\Requests\BaseFormRequest;
use App\Models\Identity;
use App\Models\Reimbursement;
use App\Models\Voucher;
use App\Rules\Base\IbanRule;
use App\Rules\FileUidRule;
use App\Scopes\Builders\VoucherQuery;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\Rule;

/**
 * @property null $reimbursement
 */
class StoreReimbursementRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return (array|string)[]
     *
     * @psalm-return array{title: 'required|string|max:200', description: 'nullable|string|min:5|max:2000', amount: string, email: array{0: 'nullable'|mixed,...}, iban: list{'required', 'string', IbanRule}, iban_name: 'required|string|min:5|max:100', voucher_id: array, state: string, files: 'required|array', 'files.*': list{'required', 'string', FileUidRule}}
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|min:5|max:2000',
            'amount' => $this->amountRule($this->identity()),
            'email' => [
                'nullable',
                ...$this->emailRules(),
            ],
            'iban' => ['required', 'string', new IbanRule()],
            'iban_name' => 'required|string|min:5|max:100',
            'voucher_id' => $this->voucherIdRule(),
            'state' => 'nullable|in:' . implode(',', [
                Reimbursement::STATE_DRAFT,
                Reimbursement::STATE_PENDING,
            ]),
            'files' => 'required|array',
            'files.*' => ['required', 'string', new FileUidRule('reimbursement_proof')],
        ];
    }

    /**
     * @param Identity $identity
     * @param Reimbursement|null $reimbursement
     * @return Builder|Relation|Voucher
     */
    protected function availableVouchers(
        Identity $identity,
        ?Reimbursement $reimbursement = null,
    ): Builder|Relation|Voucher {
        return VoucherQuery::whereAllowReimbursements($identity->vouchers(), $reimbursement);
    }

    /**
     * @param Identity $identity
     * @param Reimbursement|null $reimbursement
     * @return string
     */
    protected function amountRule(Identity $identity, ?Reimbursement $reimbursement = null): string
    {
        $voucher = $this
            ->availableVouchers($identity, $reimbursement)
            ->find($this->input('voucher_id'));

        return "required|numeric|min:.1|max:" . currency_format($voucher?->amount_available ?: 0);
    }

    /**
     * @param Reimbursement|null $reimbursement
     *
     * @return (\Illuminate\Validation\Rules\In|string)[]
     *
     * @psalm-return list{'required', \Illuminate\Validation\Rules\In}
     */
    protected function voucherIdRule(?Reimbursement $reimbursement = null): array
    {
        $query = $this->availableVouchers($this->identity(), $reimbursement);

        return [
            'required',
            Rule::in($query->get()->pluck('id')->values()->toArray()),
        ];
    }

    /**
     * @return (\Illuminate\Contracts\Translation\Translator|array|null|string)[]
     *
     * @psalm-return array{voucher_address: 'tegoeden', 'files.*': \Illuminate\Contracts\Translation\Translator|array|null|string}
     */
    public function attributes(): array
    {
        return [
            'voucher_address' => 'tegoeden',
            'files.*' => trans('validation.attributes.file'),
        ];
    }

    /**
     * @return (\Illuminate\Contracts\Translation\Translator|array|null|string)[]
     *
     * @psalm-return array{'files.required': \Illuminate\Contracts\Translation\Translator|array|null|string}
     */
    public function messages(): array
    {
        return [
            'files.required' => trans('validation.reimbursement.files.required'),
        ];
    }
}
