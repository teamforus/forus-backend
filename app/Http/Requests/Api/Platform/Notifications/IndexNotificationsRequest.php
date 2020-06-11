<?php

namespace App\Http\Requests\Api\Platform\Notifications;

use App\Models\Employee;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexNotificationsRequest extends FormRequest
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
        return [
            'seen' => 'nullable|boolean',
            'per_page' => 'numeric|max:100',
            'mark_read' => 'nullable|boolean',
            'organization_id' => [
                'nullable',
                Rule::exists('organizations', 'id')->where(static function (Builder $builder) {
                    $builder->whereIn('id', Employee::whereIdentityAddress(
                        auth_address()
                    )->pluck('organization_id'));
                }),
            ],
        ];
    }
}
