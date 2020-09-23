<?php

namespace App\Http\Requests\Api\Platform\Prevalidations;

use App\Models\Fund;
use App\Models\Organization;
use App\Rules\PrevalidationItemHasRequiredKeysRule;
use App\Rules\PrevalidationItemRule;
use App\Services\Forus\Record\Models\RecordType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePrevalidationsRequest extends FormRequest
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
        $fund = Fund::find($this->input('fund_id'));

        return array_merge([
            'fund_id' => [
                'required',
                Rule::in($this->getAvailableFunds())
            ],
            'data' => [
                'required',
                'array',
                new PrevalidationItemHasRequiredKeysRule($fund),
            ],
            'data.*' => [
                'required',
            ]
        ], array_fill_keys($this->getRequiredKeysRules($fund), [
            'required', new PrevalidationItemRule($fund)
        ]));
    }

    /**
     * @param Fund|null $fund
     * @param string $prefix
     * @return array
     */
    private function getRequiredKeysRules(?Fund $fund, string $prefix = 'data.'): array
    {
        // all required keys must be present and validated by criteria
        return $fund ? array_map(static function($key) use ($prefix) {
            return $prefix . $key;
        }, $fund->requiredPrevalidationKeys()->toArray()) : [];
    }

    /**
     * @return array
     */
    private function getAvailableFunds(): array
    {
        return Organization::queryByIdentityPermissions(auth_address(),[
            'validate_records'
        ])->get()->pluck('funds')->flatten()->filter(static function ($fund) {
            return $fund->state !== Fund::STATE_CLOSED;
        })->pluck('id')->toArray();
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return RecordType::get()->mapWithKeys(static function(RecordType $recordType) {
            return [
                sprintf('data.%s', $recordType->key) => strtolower($recordType->name)
            ];
        })->toArray();
    }

    /**
     * @return array
     */
    public function messages(): array
    {
        if ($fund = Fund::find($this->input('fund_id'))) {
            return [
                "data.{$fund->fund_config->csv_primary_key}.in" => trans('validation.unique')
            ];
        }

        return parent::messages();
    }
}
