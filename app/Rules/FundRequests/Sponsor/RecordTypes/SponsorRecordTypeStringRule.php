<?php

namespace App\Rules\FundRequests\Sponsor\RecordTypes;

class SponsorRecordTypeStringRule extends SponsorBaseRecordTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'nullable',
            'string',
            'max:200',
        ];
    }
}
