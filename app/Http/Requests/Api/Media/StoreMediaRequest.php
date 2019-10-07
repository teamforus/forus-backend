<?php

namespace App\Http\Requests\Api\Media;

use App\Rules\MediaTypeRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreMediaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'file' => 'required|file|image|mimes:jpg,jpeg,png|max:4096',
            'type' => ['required', new MediaTypeRule()],
        ];
    }
}
