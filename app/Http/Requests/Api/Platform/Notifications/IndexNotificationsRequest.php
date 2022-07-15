<?php

namespace App\Http\Requests\Api\Platform\Notifications;

use App\Http\Requests\BaseFormRequest;
use App\Models\Employee;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class IndexNotificationsRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->isAuthenticated();
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
                Rule::exists('organizations', 'id')->where(function (Builder $builder) {
                    $builder->whereIn('id', Employee::where([
                        'identity_address' => $this->auth_address(),
                    ])->pluck('organization_id'));
                }),
            ],
        ];
    }
}
