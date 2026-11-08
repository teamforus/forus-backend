<?php

namespace App\Http\Requests\Api\Platform\Organizations\Implementations\DCPConnections;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use App\Services\DCPService\Models\DCPConnection;
use Illuminate\Support\Facades\Config;
use Illuminate\Validation\Rule;

class UpdateDCPConnectionRequest extends BaseFormRequest
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
        $types = DCPConnection::orderableTypes();
        /** @var Implementation $implementation */
        $implementation = $this->route('implementation');
        $hasPassword = (bool) $implementation?->dcp_connection?->password;

        return [
            'enabled' => 'required|boolean',
            'url' => ['required', 'string', Rule::in(Config::get('dcp.allowed_urls', []))],
            'username' => 'required_if_accepted:enabled|nullable|string|max:200',
            'password' => [
                $hasPassword ? 'nullable' : 'required_if_accepted:enabled',
                'nullable',
                'string',
                'max:200',
            ],
            'order_types' => ['nullable', 'array:' . implode(',', array_keys($types))],
            ...array_merge([], ...array_map(
                fn (string $type, string $class) => $this->orderTypeRules($type, array_keys($class::dcpOrderSourceFields())),
                array_keys($types),
                $types,
            )),
        ];
    }

    /**
     * @return array
     */
    public function attributes(): array
    {
        return [
            'url' => trans('dcp.attributes.url'),
            'username' => trans('dcp.attributes.username'),
            'password' => trans('dcp.attributes.password'),
            'order_types.*.label' => trans('dcp.attributes.label'),
            'order_types.*.item' => trans('dcp.attributes.item'),
            'order_types.*.tags' => trans('dcp.attributes.tags'),
            'order_types.*.tags.*.key' => trans('dcp.attributes.tag_key'),
            'order_types.*.tags.*.source' => trans('dcp.attributes.tag_source'),
            'order_types.*.tags.*.value' => trans('dcp.attributes.tag_value'),
            'order_types.*.tags.*.format' => trans('dcp.attributes.tag_format'),
            'order_types.*.tags.*.max' => trans('dcp.attributes.tag_max'),
        ];
    }

    /**
     * @param string $type
     * @param array $sourceFields
     * @return array
     */
    protected function orderTypeRules(string $type, array $sourceFields): array
    {
        $prefix = "order_types.$type";

        return [
            "$prefix" => 'nullable|array:label,item,tags',
            "$prefix.label" => "required_with:$prefix|string|max:100",
            "$prefix.item" => 'nullable|string|max:100',
            "$prefix.tags" => "required_with:$prefix|array|min:1|max:50",
            "$prefix.tags.*" => 'required|array:key,source,value,required,format,max',
            // "key" is the key of the tag in the order row
            "$prefix.tags.*.key" => 'required|string|max:100|regex:/^[A-Za-z0-9_]+$/|not_in:key|distinct',
            "$prefix.tags.*.source" => [
                "required_without:$prefix.tags.*.value",
                'nullable',
                'string',
                Rule::in($sourceFields),
            ],
            "$prefix.tags.*.value" => 'nullable|string|max:200',
            "$prefix.tags.*.required" => 'nullable|boolean',
            "$prefix.tags.*.format" => ['nullable', 'string', 'max:30', 'regex:/^(upper|lower|date:[A-Za-z\/\-. ]+)$/'],
            "$prefix.tags.*.max" => 'nullable|integer|min:1|max:1000',
        ];
    }
}
