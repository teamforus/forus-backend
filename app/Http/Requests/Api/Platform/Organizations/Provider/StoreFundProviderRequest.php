<?php

namespace App\Http\Requests\Api\Platform\Organizations\Provider;

use App\Models\Organization;
use App\Rules\FundApplicableRule;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Class StoreFundProviderRequest
 * @property Organization|null $organization
 * @package App\Http\Requests\Api\Platform\Organizations\Provider
 */
class StoreFundProviderRequest extends FormRequest
{

}
