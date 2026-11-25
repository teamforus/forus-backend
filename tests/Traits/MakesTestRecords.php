<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\Profile;
use App\Models\ProfileRecord;
use App\Models\Record;
use App\Models\RecordType;
use Throwable;

trait MakesTestRecords
{
    /**
     * @param Organization|null $organization
     * @param array $attributes
     * @return RecordType
     */
    protected function makeProfileRecordType(?Organization $organization = null, array $attributes = []): RecordType
    {
        return RecordType::create([
            'key' => 'c05_profile_' . token_generator()->generate(16),
            'type' => RecordType::TYPE_STRING,
            'organization_id' => $organization?->id,
            'profile_records' => true,
            ...$attributes,
        ]);
    }

    /**
     * @param Profile $profile
     * @param RecordType $type
     * @param string $value
     * @param array $attributes
     * @return ProfileRecord
     */
    protected function makeProfileRecord(
        Profile $profile,
        RecordType $type,
        string $value,
        array $attributes = [],
    ): ProfileRecord {
        return $profile->profile_records()->create([
            'record_type_id' => $type->id, 'value' => $value, ...$attributes,
        ]);
    }

    /**
     * @param Identity $identity
     * @param Identity $validator
     * @param array $records
     * @return void
     * @throws Throwable
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
