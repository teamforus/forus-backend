<?php

namespace App\Http\Requests\Api\Platform\Organizations\Reimbursements;

use App\Models\Organization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * @property-read Organization $organization
 */
class UpdateReimbursementRequest extends FormRequest
{

}
