<?php

namespace App\Http\Requests\Api\Platform\FirestoreTokens;

use App\Http\Requests\BaseFormRequest;
use App\Models\SystemConfig;

class StoreFirestoreTokenRequest extends BaseFormRequest
{


    /**
     * Get the validation rules that apply to the request.
     *
     * @return string[]
     *
     * @psalm-return array{key: string}
     */
    public function rules(): array
    {
        $keys = SystemConfig::where('key', 'firestore_key')->pluck('value');

        return [
            'key' => 'required|in:' . $keys->implode(',')
        ];
    }
}
