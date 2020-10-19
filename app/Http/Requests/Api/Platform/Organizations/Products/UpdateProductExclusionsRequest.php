<?php

namespace App\Http\Requests\Api\Platform\Organizations\Products;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class UpdateProductExclusionsRequest
 * @property Product $product
 * @package App\Http\Requests\Api\Platform\Organizations\Products
 */
class UpdateProductExclusionsRequest extends FormRequest
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
        $funds = $this->product->organization->fund_providers->pluck('fund_id');

        return [
            'enable_funds'      => 'nullable|array',
            'enable_funds.*'    => 'required|exists:funds,id|in:' . $funds->join(','),
            'disable_funds'     => 'nullable|array',
            'disable_funds.*'   => 'required|exists:funds,id|in:' . $funds->join(','),
        ];
    }
}
    