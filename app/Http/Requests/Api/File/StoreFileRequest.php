<?php

namespace App\Http\Requests\Api\File;

use Illuminate\Foundation\Http\FormRequest;

class StoreFileRequest extends FormRequest
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
            'file' => [
                'required',
                'file',
                'mimes:' . join(',', config('file.allowed_types', [])),
                'max:' . config('file.max_file_size', 2000)
            ]
        ];
    }
}
