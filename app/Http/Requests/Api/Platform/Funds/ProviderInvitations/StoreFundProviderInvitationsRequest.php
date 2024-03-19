<?php

namespace App\Http\Requests\Api\Platform\Funds\ProviderInvitations;

use App\Models\Fund;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Class StoreFundProviderInvitationsRequest
 * @property Fund|null $fund
 * @package App\Http\Requests\Api\Platform\Funds\ProviderInvitations
 */
class StoreFundProviderInvitationsRequest extends FormRequest
{

}
