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

        $physicalCardType = $voucher?->fund?->physical_card_types()
            ->where('physical_card_types.id', $this->post('physical_card_type_id'))
            ->first();

        if (!$physicalCardType) {
            return [
                'code' => 'required|in:',
                'physical_card_type_id' => 'required|in:',
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
            'physical_card_type_id' => [
                'required',
                Rule::exists('physical_card_types', 'id')
                    ->whereIn('physical_card_types.id', $voucher->fund->physical_card_types->pluck('id')->toArray()),
            ],
        ];
    }
}
