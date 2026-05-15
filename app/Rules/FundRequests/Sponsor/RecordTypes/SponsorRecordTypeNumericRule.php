<?php

namespace App\Rules\FundRequests\Sponsor\RecordTypes;

class SponsorRecordTypeNumericRule extends SponsorBaseRecordTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'nullable',
            'numeric',
        ];
    }
}
