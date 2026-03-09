<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Prevalidation;
use App\Models\Record;
use App\Models\RecordType;
use App\Models\RecordValidation;
use App\Models\Voucher;

trait MakesRequesterVoucherPayouts
{
    /**
     * @param Organization $organization
     * @param Implementation|null $implementation
     * @param array $fundConfigsData
     * @return Fund
     */
    protected function makePayoutEnabledFund(
        Organization $organization,
        ?Implementation $implementation = null,
        array $fundConfigsData = [],
    ): Fund {
        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'allow_voucher_payouts' => true,
            ...$fundConfigsData,
        ], implementation: $implementation);

        [$ibanKey, $ibanNameKey] = $this->getPayoutIbanRecordKeys();

        if (!RecordType::where('type', RecordType::TYPE_IBAN)->where('key', $ibanKey)->exists()) {
            $this->makeRecordType($organization, RecordType::TYPE_IBAN, $ibanKey);
        }

        if (!RecordType::where('type', RecordType::TYPE_STRING)->where('key', $ibanNameKey)->exists()) {
            $this->makeRecordType($organization, RecordType::TYPE_STRING, $ibanNameKey);
        }

        $fund->fund_config->forceFill([
            'allow_voucher_payouts' => true,
            'iban_record_key' => $ibanKey,
            'iban_name_record_key' => $ibanNameKey,
        ])->save();

        $organization->forceFill([
            'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
        ])->save();

        $fund->syncCriteria([[
            'record_type_key' => $ibanKey,
            'operator' => '*',
            'value' => '',
            'show_attachment' => false,
        ], [
            'record_type_key' => $ibanNameKey,
            'operator' => '*',
            'value' => '',
            'show_attachment' => false,
        ]]);

        return $fund->refresh();
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param string|null $iban
     * @param string|null $ibanName
     * @return array{fund_request: FundRequest, voucher: Voucher}
     */
    protected function makePayoutVoucherViaApplication(
        Identity $identity,
        Fund $fund,
        ?string $iban = null,
        ?string $ibanName = null,
    ): array {
        $iban = $iban ?: $this->makeIban();
        $ibanName = $ibanName ?: $this->makeIbanName();
        [$ibanKey, $ibanNameKey] = $this->getPayoutIbanRecordKeys();

        $this->assertEquals($ibanKey, $fund->fund_config->iban_record_key);
        $this->assertEquals($ibanNameKey, $fund->fund_config->iban_name_record_key);

        $response = $this->apiMakeFundRequestRequest($identity, $fund, [
            'records' => [
                $this->makeRequestCriterionValue($fund, $ibanKey, $iban),
                $this->makeRequestCriterionValue($fund, $ibanNameKey, $ibanName),
            ],
        ], false);

        $response->assertSuccessful();

        $fundRequest = FundRequest::find($response->json('data.id'));
        $this->assertNotNull($fundRequest);

        $employee = $fund->organization->employees->first();
        $this->assertNotNull($employee);

        $this->apiFundRequestAssignRequest($fundRequest, $employee)->assertSuccessful();
        $this->apiFundRequestApproveRequest($fundRequest, $employee)->assertSuccessful();

        $voucher = Voucher::where('fund_request_id', $fundRequest->id)
            ->where('identity_id', $identity->id)
            ->first();

        $this->assertNotNull($voucher);

        return [
            'iban' => $iban,
            'iban_name' => $ibanName,
            'fund_request' => $fundRequest->refresh(),
            'voucher' => $voucher->refresh(),
        ];
    }

    /**
     * @return array{string, string}
     */
    private function getPayoutIbanRecordKeys(): array
    {
        return ['iban_requester_payout', 'iban_name_requester_payout'];
    }

    /**
     * @param Organization $organization
     * @param string $recordTypeKey
     * @return RecordType
     */
    protected function ensureNumberRecordType(Organization $organization, string $recordTypeKey): RecordType
    {
        $recordType = RecordType::query()
            ->where('organization_id', $organization->id)
            ->where('key', $recordTypeKey)
            ->first();

        if ($recordType) {
            if (!$recordType->criteria) {
                $recordType->forceFill(['criteria' => true])->save();
            }

            return $recordType;
        }

        return RecordType::create([
            'organization_id' => $organization->id,
            'criteria' => true,
            'type' => RecordType::TYPE_NUMBER,
            'key' => $recordTypeKey,
        ]);
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param FundRequest $fundRequest
     * @param string $recordTypeKey
     * @param string|float $value
     * @return void
     */
    protected function createTrustedRecord(
        Identity $identity,
        Fund $fund,
        FundRequest $fundRequest,
        string $recordTypeKey,
        string|float $value,
    ): void {
        $recordType = $this->ensureNumberRecordType($fund->organization, $recordTypeKey);

        Record::where('identity_address', $identity->address)
            ->where('record_type_id', $recordType->id)
            ->forceDelete();

        $record = Record::create([
            'identity_address' => $identity->address,
            'record_type_id' => $recordType->id,
            'fund_request_id' => $fundRequest->id,
            'organization_id' => $fund->organization_id,
            'value' => (string) $value,
            'order' => 0,
        ]);

        $prevalidation = Prevalidation::create([
            'uid' => token_generator()->generate(32),
            'identity_address' => $identity->address,
            'fund_id' => $fund->id,
            'organization_id' => $fund->organization_id,
            'state' => Prevalidation::STATE_PENDING,
            'validated_at' => now(),
        ]);

        RecordValidation::create([
            'record_id' => $record->id,
            'state' => RecordValidation::STATE_APPROVED,
            'uuid' => token_generator()->generate(64),
            'identity_address' => $identity->address,
            'organization_id' => $fund->organization_id,
            'prevalidation_id' => $prevalidation->id,
        ]);
    }
}
