<?php

namespace App\Http\Requests\Api\Platform\Organizations\Sponsor\TransactionBulks;

use App\Http\Requests\BaseFormRequest;
use App\Models\Organization;

/**
 * Class IndexTransactionBulksExportFieldsRequest
 * @property-read Organization $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Sponsor\TransactionBulks
 */
class IndexTransactionBulksExportFieldsRequest extends BaseFormRequest
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
        return [];
    }
}