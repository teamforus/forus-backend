<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\Identity;
use App\Models\RecordType;
use App\Models\Organization;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;

class FundTrustedRecordsTest extends TestCase
{
    use DoesTesting, DatabaseTransactions, CreatesApplication, MakesTestFunds;

    /**
     * @return void
     */
    public function testFundConfigRecordsValidityDays(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));

        $requester = $this->makeIdentity();
        $this->setRecordsValue($fund->organization, $requester, 'children_nth',  2);

        $fund->fund_config->forceFill([
            'record_validity_days' => 2,
        ])->save();

        // assert record is valid right away
        $trustedRecord = $fund->getTrustedRecordOfType($requester, 'children_nth');
        self::assertEquals(2, $trustedRecord?->value);

        // assert record is one minute before is is supposed to be expired
        $this->travelTo(now()->addDays(2));
        $trustedRecord = $fund->getTrustedRecordOfType($requester, 'children_nth');
        self::assertEquals(2, $trustedRecord?->value);

        // assert record is expired
        $this->travelTo(now()->addDays(2)->addMinute());
        $trustedRecord = $fund->getTrustedRecordOfType($requester, 'children_nth');
        self::assertNull($trustedRecord);
    }

    /**
     * @return void
     */
    public function testFundConfigRecordsValidityStartDay(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $requester = $this->makeIdentity();
        $startDate = $fund->end_date->addDays(5);

        $fund->fund_config->forceFill([
            'record_validity_start_date' => $startDate,
        ])->save();

        $this->setRecordsValue($fund->organization, $requester, 'children_nth', 2);

        // assert record validated before $startDate is not valid
        self::assertNull($fund->getTrustedRecordOfType($requester, 'children_nth'));

        $this->travelTo($startDate);

        // assert record validated before $startDate is still not valid even after reaching start date
        self::assertNull($fund->getTrustedRecordOfType($requester, 'children_nth'));

        // validate records after startDate
        $this->setRecordsValue($fund->organization, $requester, 'children_nth', 2);

        // assert record validated after start date is valid
        $trustedRecord = $fund->getTrustedRecordOfType($requester, 'children_nth');
        self::assertEquals(2, $trustedRecord?->value);
    }

    /**
     * @param Organization $organization
     * @param Identity $requester
     * @param string $recordType
     * @param string|int $value
     * @return Identity
     */
    protected function setRecordsValue(
        Organization $organization,
        Identity $requester,
        string $recordType,
        string|int $value,
    ): Identity {
        $employee = $organization->addEmployee(
            $this->makeIdentity(),
            Role::byKey('validation')->pluck('id')->toArray(),
        );

        $requester->makeRecord(RecordType::findByKey($recordType), $value)
            ->makeValidationRequest()
            ->approve($employee->identity, $organization);

        return $requester;
    }
}