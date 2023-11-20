<?php

namespace App\Rules\FundRequests\RecordTypes;

use App\Rules\Base\IbanRule;

class RecordTypeIbanRule extends BaseRecordTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_filter([
            $this->isRequiredRule(),
            new IbanRule(),
        ]);
    }
}
