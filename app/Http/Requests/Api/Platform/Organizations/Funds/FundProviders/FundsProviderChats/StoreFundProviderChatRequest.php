<?php

namespace App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders\FundsProviderChats;

use App\Exceptions\MissingRequiredRequestPropertyException;
use App\Models\FundProvider;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class StoreFundProviderChatRequest
 * @property FundProvider $fund_provider
 * @package App\Http\Requests\Api\Platform\Organizations\Funds\FundProviders\FundsProviderChats
 */
class StoreFundProviderChatRequest extends FormRequest
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
     * @throws \Exception
     */
    public function rules(): array
    {
        if (!is_object($this->fund_provider) ||
            get_class($this->fund_provider) !== FundProvider::class) {
            throw new MissingRequiredRequestPropertyException();
        }

        $invalidProducts = $this->fund_provider->fund_provider_chats()->pluck('product_id');

        return [
            'product_id' => [
                'required',
                'exists:products,id',
                Rule::exists('products', 'id')
                    ->whereNotIn('id', $invalidProducts->toArray())
                    ->whereNull('sponsor_organization_id'),
            ],
            'message' => [
                'required',
                'string',
                'min:1',
                'max:2000'
            ]
        ];
    }
}
