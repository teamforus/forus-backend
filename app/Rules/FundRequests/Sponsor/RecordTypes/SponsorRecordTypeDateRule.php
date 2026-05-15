<?php

namespace App\Rules\FundRequests\Sponsor\RecordTypes;

class SponsorRecordTypeDateRule extends SponsorBaseRecordTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_filter([
            'nullable',
            'date',
            "date_format:$this->dateFormat",
        ]);
    }
}
