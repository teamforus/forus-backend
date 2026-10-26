<?php

namespace App\Http\Requests\Tvs;

use App\Models\Implementation;
use App\Models\Organization;
use App\Services\DigIdService\Models\DigIdSession;
use App\Services\DigIdService\Objects\DigIdSessionData;
use App\Services\DigIdService\Requests\BaseStartDigIdRequest;
use App\Services\DigIdService\TvsService;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

class StartTvsRequest extends BaseStartDigIdRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $fundsQuery = Implementation::activeFundsQuery();

        if ($this->input('request') === DigIdSession::SESSION_REQUEST_FUND_REQUEST) {
            $fundsQuery->where('funds.id', $this->input('fund_id'));
        }

        return [
            ...parent::rules(),
            'organization_id' => [
                'required',
                Rule::exists('organizations', 'id')->where(fn (Builder $query) => $query->whereIn(
                    'id',
                    resolve(TvsService::class)
                        ->eligibleOrganizationsQuery($fundsQuery)
                        ->select('id'),
                )),
            ],
        ];
    }

    /**
     * @return DigIdSessionData
     */
    public function sessionData(): DigIdSessionData
    {
        $organization = $this->sessionOrganization();
        $configuration = $organization->getTvsDigidConfig();

        return $this->makeSessionData(
            $organization,
            DigIdSession::CONNECTION_TYPE_TVS,
            $configuration['entity_id'],
            $configuration['service_uuid'],
        );
    }

    /**
     * @return Organization
     */
    public function sessionOrganization(): Organization
    {
        return Organization::findOrFail($this->input('organization_id'));
    }
}
