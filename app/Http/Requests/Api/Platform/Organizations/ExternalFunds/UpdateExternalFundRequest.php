<?php

namespace App\Http\Requests\Api\Platform\Organizations\ExternalFunds;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Organization;
use App\Scopes\Builders\FundCriteriaQuery;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class UpdateExternalFundRequest
 * @property Organization $organization
 * @property Fund $fund
 * @package App\Http\Requests\Api\Platform\Organizations\ExternalFunds
 */
class UpdateExternalFundRequest extends FormRequest
{

}
