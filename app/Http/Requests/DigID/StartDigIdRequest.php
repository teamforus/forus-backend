<?php

namespace App\Http\Requests\DigID;

use App\Models\Fund;
use App\Models\Organization;
use App\Services\DigIdService\Models\DigIdSession;
use App\Services\DigIdService\Objects\DigIdSessionData;
use App\Services\DigIdService\Requests\BaseStartDigIdRequest;

class StartDigIdRequest extends BaseStartDigIdRequest
{
    /**
     * @return DigIdSessionData
     */
    public function sessionData(): DigIdSessionData
    {
        return $this->makeSessionData($this->sessionOrganization(), $this->implementation()->digid_connection_type);
    }

    /**
     * @return Organization
     */
    public function sessionOrganization(): Organization
    {
        return $this->input('request') === DigIdSession::SESSION_REQUEST_FUND_REQUEST
            ? Fund::findOrFail($this->input('fund_id'))->organization()->firstOrFail()
            : $this->implementation()->organization()->firstOrFail();
    }
}
