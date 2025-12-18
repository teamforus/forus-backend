<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\RecordType;
use App\Models\Voucher;

trait MakesRequesterVoucherPayouts
{
    /**
     * @param Organization $organization
     * @param Implementation|null $implementation
     * @return Fund
     */
    protected function makePayoutEnabledFund(
        Organization $organization,
        ?Implementation $implementation = null,
    ): Fund {
        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'allow_voucher_payouts' => true,
        ], implementation: $implementation);

        [$ibanKey, $ibanNameKey] = $this->getPayoutIbanRecordKeys();

        $this->makeRecordType($organization, RecordType::TYPE_IBAN, $ibanKey);
        $this->makeRecordType($organization, RecordType::TYPE_STRING, $ibanNameKey);

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
     * @param string $iban
     * @param string $ibanName
     * @return array{fund_request: FundRequest, voucher: Voucher}
     */
    protected function makePayoutVoucherViaApplication(
        Identity $identity,
        Fund $fund,
        string $iban,
        string $ibanName,
    ): array {
        $identity->setBsnRecord('123456789');

        $fund->loadMissing('fund_config');

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
}
