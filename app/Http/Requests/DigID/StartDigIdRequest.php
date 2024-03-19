<?php

namespace App\Http\Requests\DigID;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
use Illuminate\Validation\Rule;

class StartDigIdRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return ((\Illuminate\Validation\Rules\Exists|string)[]|string)[]
     *
     * @psalm-return array{request: string, fund_id: list{'required_if:redirect_type,fund_request', \Illuminate\Validation\Rules\Exists}}
     */
    public function rules(): array
    {
        $redirectTypes = [
            'fund_request', 'auth'
        ];

        return [
            'request' => 'required|in:' . implode(',', $redirectTypes),
            'fund_id' => [
                'required_if:redirect_type,fund_request',
                Rule::exists('funds', 'id')->whereIn(
                    'id',
                    Implementation::activeFundsQuery()->pluck('id')->toArray()
                )
            ]
        ];
    }
}
