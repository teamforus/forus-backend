<?php

namespace App\Rules\FundRequests\RecordTypes;

class RecordTypeEmailRule extends BaseRecordTypeRule
{
    /**
     * @return string[]
     *
     * @psalm-return array{0?: string, 1: 'email'}
     */
    public function rules(): array
    {
        return array_filter([
            $this->isRequiredRule(),
            'email',
        ]);
    }
}
