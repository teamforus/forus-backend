<?php

namespace App\Http\Requests\Api\Platform\Organizations\BIConnections;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;
use App\Services\BIConnectionService\BIConnectionService;
use App\Services\BIConnectionService\Models\BIConnection;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
class StoreBIConnectionRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->organization->allow_bi_connection;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     * @throws \Throwable
     */
    public function rules(): array
    {
        $enable = $this->request->get('enabled');
        $dataTypes = BIConnectionService::create($this->organization)->getDataTypes();

        return [
            'enabled' => 'required|boolean',
            ...$enable ? [
                'expiration_period' => [
                    'required',
                    Rule::in(BIConnection::EXPIRATION_PERIODS),
                ],
                'ips' => 'required|array',
                'ips.*' => 'required|ip',
                'data_types' => 'required|array',
                'data_types.*' => [
                    'required',
                    Rule::in(Arr::pluck($dataTypes, 'key')),
                ],
            ] : [],
        ];
    }


    /**
     * @return array
     */
    public function messages(): array
    {
        $attribute = trans('validation.attributes.ip');

        return [
            'ips.*.required' => trans('validation.required', compact('attribute')),
            'ips.*.ip' => trans('validation.ip', compact('attribute')),
        ];
    }

    /**
     * @return string[]
     */
    public function attributes(): array
    {
        return [
            'ips' => trans('validation.attributes.ip'),
        ];
    }
}
