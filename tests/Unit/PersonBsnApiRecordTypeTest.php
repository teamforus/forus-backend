<?php

namespace Tests\Unit;

use App\Models\PersonBsnApiRecordType;
use App\Models\RecordType;
use Tests\CreatesApplication;
use Tests\TestCase;

class PersonBsnApiRecordTypeTest extends TestCase
{
    use CreatesApplication;

    /**
     * @return void
     */
    public function testParsePersonValueReturnsNullForInvalidDate(): void
    {
        $recordType = new PersonBsnApiRecordType();

        $this->assertNull($recordType->parsePersonValue('not-a-date', RecordType::CONTROL_TYPE_DATE));
    }

    /**
     * @return void
     */
    public function testParsePersonValueCastsStepAndCurrency(): void
    {
        $recordType = new PersonBsnApiRecordType();

        $this->assertSame(12, $recordType->parsePersonValue('12', RecordType::CONTROL_TYPE_STEP));
        $this->assertEquals(12.5, $recordType->parsePersonValue('12.5', RecordType::CONTROL_TYPE_CURRENCY));
    }
}
