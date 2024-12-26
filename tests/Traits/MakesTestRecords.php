<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Record;

trait MakesTestRecords
{
    /**
     * @param Identity $identity
     * @param Identity $validator
     * @param array $records
     * @return void
     */
    protected function makeRecords(Identity $identity, Identity $validator, array $records): void
    {
        /** @var Record[] $records */
        $records = $identity->addRecords($records);

        foreach ($records as $record) {
            $record->makeValidationRequest()->approve($validator);
        }
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param array $records
     * @return void
     */
    protected function assertTrustedRecords(Identity $identity, Fund $fund, array $records): void
    {
        foreach ($records as $key => $value) {
            $this->assertEquals($value, $fund->getTrustedRecordOfType($identity, $key)?->value);
        }
    }
}