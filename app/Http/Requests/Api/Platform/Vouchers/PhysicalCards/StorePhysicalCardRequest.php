<?php

namespace App\Http\Requests\Api\Platform\Vouchers\PhysicalCards;

use App\Models\Voucher;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property ?Voucher $voucher
 * @property ?Voucher $voucher_number_or_address
 */
class StorePhysicalCardRequest extends FormRequest
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
        $voucher = $this->voucher ?? $this->voucher_number_or_address;
        $fundPhysicalCardType = $voucher?->fund?->fund_physical_card_types()->find($this->post('fund_physical_card_type_id'));
        $physicalCardType = $fundPhysicalCardType?->physical_card_type;

        if (!$fundPhysicalCardType || !$physicalCardType) {
            return [
                'code' => 'required|in:',
                'fund_physical_card_type_id' => 'required|in:',
            ];
        }

        return [
            'code' => array_filter([
                'required',
                'string',
                'size:' . $physicalCardType->code_block_size * $physicalCardType->code_blocks,
                $physicalCardType->code_prefix ? 'starts_with:' . $physicalCardType->code_prefix : null,
                Rule::unique('physical_cards', 'code')
                    ->where('physical_card_type_id', $physicalCardType->id),
            ]),
            'fund_physical_card_type_id' => [
                'required',
                Rule::exists('fund_physical_card_types', 'id')->where('fund_id', $voucher->fund_id),
            ],
        ];
    }
}
