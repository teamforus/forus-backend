<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations;

use App\Rules\MediaUidRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateImplementationCmsRequest extends FormRequest
{


    /**
     * @return string[]
     *
     * @psalm-return array{show_home_map: 'nullable|bool', show_office_map: 'nullable|boolean', show_home_products: 'nullable|boolean', show_providers_map: 'nullable|boolean', show_provider_map: 'nullable|boolean', show_voucher_map: 'nullable|boolean', show_product_map: 'nullable|boolean'}
     */
    private function showBlockFlags(): array
    {
        return [
            'show_home_map' => 'nullable|bool',
            'show_office_map' => 'nullable|boolean',
            'show_home_products' => 'nullable|boolean',
            'show_providers_map' => 'nullable|boolean',
            'show_provider_map' => 'nullable|boolean',
            'show_voucher_map'  => 'nullable|boolean',
            'show_product_map'  => 'nullable|boolean',
        ];
    }

    /**
     * @return string[]
     *
     * @psalm-return array{announcement: 'nullable|array', 'announcement.type': 'nullable|in:warning,danger,success,primary,default', 'announcement.title': 'nullable|string|max:2000', 'announcement.description': 'nullable|string|max:8000', 'announcement.expire_at': 'nullable|date_format:Y-m-d', 'announcement.active': 'nullable|boolean', 'announcement.replace': 'nullable|boolean'}
     */
    private function announcementsRules(): array
    {
        return [
            'announcement'              => 'nullable|array',
            'announcement.type'         => 'nullable|in:warning,danger,success,primary,default',
            'announcement.title'        => 'nullable|string|max:2000',
            'announcement.description'  => 'nullable|string|max:8000',
            'announcement.expire_at'    => 'nullable|date_format:Y-m-d',
            'announcement.active'       => 'nullable|boolean',
            'announcement.replace'      => 'nullable|boolean',
        ];
    }

    /**
     * @return string[]
     *
     * @psalm-return array{'announcement.title': 'titel', 'announcement.description': 'description'}
     */
    public function attributes(): array
    {
        return [
            'announcement.title' => 'titel',
            'announcement.description' => 'description',
        ];
    }
}
