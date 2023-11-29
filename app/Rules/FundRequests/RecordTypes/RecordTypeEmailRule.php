<?php

namespace App\Rules\FundRequests\RecordTypes;

class RecordTypeEmailRule extends BaseRecordTypeRule
{
    /**
     * @return array
     */
    public function rules(): array
    {
        return array_filter([
            $this->isRequiredRule(),
            'email',
        ]);
    }
}
