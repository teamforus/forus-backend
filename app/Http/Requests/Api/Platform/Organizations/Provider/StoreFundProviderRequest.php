<?php

namespace App\Http\Requests\Api\Platform\Organizations\Provider;

use App\Models\Organization;
use App\Rules\FundApplicableRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreFundProviderRequest
 * @property Organization|null $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Provider
 */
class StoreFundProviderRequest extends FormRequest
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
        return [
            'fund_id' => [
                'required',
                new FundApplicableRule($this->organization)
            ]
        ];
    }
}
