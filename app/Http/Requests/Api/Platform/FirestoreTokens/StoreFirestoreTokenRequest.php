<?php

namespace App\Http\Requests\Api\Platform\FirestoreTokens;

use App\Http\Requests\BaseFormRequest;
use App\Models\SystemConfig;

class StoreFirestoreTokenRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated() && $this->isMeApp();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $keys = SystemConfig::where('key', 'firestore_key')->pluck('value');

        return [
            'key' => 'required|in:' . $keys->implode(',')
        ];
    }
}
