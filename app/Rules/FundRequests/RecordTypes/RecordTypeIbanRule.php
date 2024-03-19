<?php

namespace App\Rules\FundRequests\RecordTypes;

use App\Rules\Base\IbanRule;

class RecordTypeIbanRule extends BaseRecordTypeRule
{
    /**
     * @return (IbanRule|string)[]
     *
     * @psalm-return array{0?: string, 1: IbanRule}
     */
    public function rules(): array
    {
        return array_filter([
            $this->isRequiredRule(),
            new IbanRule(),
        ]);
    }
}
