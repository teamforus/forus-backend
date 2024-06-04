<?php

namespace App\Http\Requests\Api\Platform\Organizations\MollieConnections;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Services\MollieService\Models\MollieConnection;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 * @property-read MollieConnection $mollie_connection
 */
class UpdateMollieConnectionRequest extends BaseFormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'mollie_connection_profile_id' => [
                'required',
                Rule::exists('mollie_connection_profiles', 'id')->where(function($query) {
                    $query->where('mollie_connection_id', $this->organization->mollie_connection->id);
                })
            ],
        ];
    }
}
