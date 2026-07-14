<?php

namespace Tests\Unit\Searches\Sponsor;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\FundRequestRecord;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\ProfileRelation;
use App\Models\Voucher;
use App\Models\VoucherTransaction;
use App\Searches\Sponsor\IdentitiesSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;
use InvalidArgumentException;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestHousehold;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestReimbursements;
use Tests\Unit\Searches\SearchTestCase;
use Throwable;

class IdentitiesSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestHousehold;
    use MakesTestFundRequests;
    use MakesTestOrganizations;
    use MakesTestReimbursements;

    /**
     * @return void
     */
    public function testRequiresOrganizationId(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $search = new IdentitiesSearch([], Identity::query());

        $search->query();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByQueryBaseFields(): void
    {
        $emailPart1 = 'unique';
        $emailPart2 = 'other';

        $emailVerifiedPart1 = 'first';
        $emailVerifiedPart2 = 'second';

        $bsnPart1 = '11111';
        $bsnPart2 = '22222';

        $notePart1 = 'interesting';
        $notePart2 = 'shorter';

        $profileBankAccountNamePart1 = 'profile_account_name_one';
        $profileBankAccountNamePart2 = 'profile_account_name_two';

        $profileBankAccountIbanPart1 = '33333';
        $profileBankAccountIbanPart2 = '44444';

        $fundRequestBankAccountNamePart1 = 'fund_request_bank_account_name_one';
        $fundRequestBankAccountNamePart2 = 'fund_request_bank_account_name_two';

        $fundRequestBankAccountIbanPart1 = '12345';
        $fundRequestBankAccountIbanPart2 = '54321';

        $reimbursementBankAccountNamePart1 = 'reimbursement_bank_account_name_one';
        $reimbursementBankAccountNamePart2 = 'reimbursement_bank_account_name_two';

        $reimbursementBankAccountIbanPart1 = '12333';
        $reimbursementBankAccountIbanPart2 = '54333';

        $payoutBankAccountNamePart1 = 'payout_bank_account_name_one';
        $payoutBankAccountNamePart2 = 'payout_bank_account_name_two';

        $payoutBankAccountIbanPart1 = '12444';
        $payoutBankAccountIbanPart2 = '54444';

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $employee = $organization->employees()->first();

        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'iban_record_key' => 'iban',
            'iban_name_record_key' => 'iban_name',
            'allow_reimbursements' => true,
        ]);

        $identity1 = $this->makeIdentity($this->makeUniqueEmail($emailPart1), "{$bsnPart1}555");
        $identity1->addEmail($this->makeUniqueEmail($emailVerifiedPart1), true);
        $identity1->addNote("$notePart1 identity note", $employee);

        $organization->findOrMakeProfile($identity1)->profile_bank_accounts()->create([
            'iban' => "{$profileBankAccountIbanPart1}99999",
            'name' => "$profileBankAccountNamePart1 Bank Account name",
        ]);

        $identity2 = $this->makeIdentity($this->makeUniqueEmail($emailPart2), "{$bsnPart2}555");
        $identity2->addEmail($this->makeUniqueEmail($emailVerifiedPart2), true);
        $identity2->addNote("$notePart2 identity note", $employee);

        $organization->findOrMakeProfile($identity2)->profile_bank_accounts()->create([
            'iban' => "{$profileBankAccountIbanPart2}99999",
            'name' => "$profileBankAccountNamePart2 Bank Account name",
        ]);

        $fund->makeVoucher($identity1);
        $fund->makeVoucher($identity2);

        // assert by primary email
        $this->assertSearchIds([
            'q' => $emailPart1,
            'organization_id' => $organization->id,
        ], [$identity1->id]);

        $this->assertSearchIds([
            'q' => $emailPart2,
            'organization_id' => $organization->id,
        ], [$identity2->id]);

        // assert by verified email
        $this->assertSearchIds([
            'q' => $emailVerifiedPart1,
            'organization_id' => $organization->id,
        ], [$identity1->id]);

        $this->assertSearchIds([
            'q' => $emailVerifiedPart2,
            'organization_id' => $organization->id,
        ], [$identity2->id]);

        // assert by bsn
        $this->assertSearchIds([
            'q' => $bsnPart1,
            'organization_id' => $organization->id,
        ], [$identity1->id]);

        $this->assertSearchIds([
            'q' => $bsnPart2,
            'organization_id' => $organization->id,
        ], [$identity2->id]);

        // assert by note
        $this->assertSearchIds([
            'q' => $notePart1,
            'organization_id' => $organization->id,
        ], [$identity1->id]);

        $this->assertSearchIds([
            'q' => $notePart2,
            'organization_id' => $organization->id,
        ], [$identity2->id]);

        $this->assertBankAccountSearch($organization, [[
            'iban' => $profileBankAccountIbanPart1,
            'iban_name' => $profileBankAccountNamePart1,
            'identity' => $identity1,
        ], [
            'iban' => $profileBankAccountIbanPart2,
            'iban_name' => $profileBankAccountNamePart2,
            'identity' => $identity2,
        ]]);

        // prepare fund requests as bank accounts
        $this->makeTestFundRequestAsBankAccount(
            $identity1,
            $fund,
            "{$fundRequestBankAccountIbanPart1}99999",
            "$fundRequestBankAccountNamePart1 Bank Account name"
        );

        $this->makeTestFundRequestAsBankAccount(
            $identity2,
            $fund,
            "{$fundRequestBankAccountIbanPart2}99999",
            "$fundRequestBankAccountNamePart2 Bank Account name"
        );

        $this->assertBankAccountSearch($organization, [[
            'iban' => $fundRequestBankAccountIbanPart1,
            'iban_name' => $fundRequestBankAccountNamePart1,
            'identity' => $identity1,
        ], [
            'iban' => $fundRequestBankAccountIbanPart2,
            'iban_name' => $fundRequestBankAccountNamePart2,
            'identity' => $identity2,
        ]]);

        // prepare reimbursements and assert bank account search
        $voucher1 = $fund->makeVoucher($identity1);
        $voucher2 = $fund->makeVoucher($identity2);

        $reimbursement1 = $this->makeReimbursement($voucher1, true);
        $reimbursement2 = $this->makeReimbursement($voucher2, true);

        $reimbursement1->update([
            'iban' => "{$reimbursementBankAccountIbanPart1}99999",
            'iban_name' => "$reimbursementBankAccountNamePart1 Bank Account name",
        ]);

        $reimbursement2->update([
            'iban' => "{$reimbursementBankAccountIbanPart2}99999",
            'iban_name' => "$reimbursementBankAccountNamePart2 Bank Account name",
        ]);

        $reimbursement1->assign($employee)->approve();
        $reimbursement2->assign($employee)->approve();

        $this->assertBankAccountSearch($organization, [[
            'iban' => $reimbursementBankAccountIbanPart1,
            'iban_name' => $reimbursementBankAccountNamePart1,
            'identity' => $identity1,
        ], [
            'iban' => $reimbursementBankAccountIbanPart2,
            'iban_name' => $reimbursementBankAccountNamePart2,
            'identity' => $identity2,
        ]]);

        // prepare payout transaction and assert bank account search
        $transactionArr = [
            'initiator' => VoucherTransaction::INITIATOR_SPONSOR,
            'target' => VoucherTransaction::TARGET_PAYOUT,
            'amount' => 10,
        ];

        $payoutVoucher1 = $fund->makeVoucher($identity1, ['voucher_type' => Voucher::VOUCHER_TYPE_PAYOUT]);
        $payoutVoucher2 = $fund->makeVoucher($identity2, ['voucher_type' => Voucher::VOUCHER_TYPE_PAYOUT]);

        $payoutVoucher1->makeTransaction([
            ...$transactionArr,
            'target_iban' => "{$payoutBankAccountIbanPart1}99999",
            'target_name' => "$payoutBankAccountNamePart1 Bank Account name",
        ]);

        $payoutVoucher2->makeTransaction([
            ...$transactionArr,
            'target_iban' => "{$payoutBankAccountIbanPart2}99999",
            'target_name' => "$payoutBankAccountNamePart2 Bank Account name",
        ]);

        $this->assertBankAccountSearch($organization, [[
            'iban' => $payoutBankAccountIbanPart1,
            'iban_name' => $payoutBankAccountNamePart1,
            'identity' => $identity1,
        ], [
            'iban' => $payoutBankAccountIbanPart2,
            'iban_name' => $payoutBankAccountNamePart2,
            'identity' => $identity2,
        ]]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProfileBankAccountQueryIsScopedToOrganization(): void
    {
        $organization1 = $this->makeTestOrganization($this->makeIdentity());
        $organization2 = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity($this->makeUniqueEmail(), $this->randomFakeBsn());

        $this->makeTestFund($organization1)->makeVoucher($identity);
        $this->makeTestFund($organization2)->makeVoucher($identity);

        $iban = $this->makeIban();
        $ibanName = 'profile_bank_account_organization_2';

        $organization2->findOrMakeProfile($identity)->profile_bank_accounts()->create([
            'iban' => $iban,
            'name' => $ibanName,
        ]);

        $this->assertBankAccountSearchIsScopedToOrganization(
            $organization1,
            $organization2,
            $identity,
            $iban,
            $ibanName,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundRequestBankAccountQueryIsScopedToOrganization(): void
    {
        $organization1 = $this->makeTestOrganization($this->makeIdentity());
        $organization2 = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity($this->makeUniqueEmail(), $this->randomFakeBsn());

        $this->makeTestFund($organization1)->makeVoucher($identity);

        $fund = $this->makeTestFund($organization2, fundConfigsData: [
            'iban_record_key' => 'iban',
            'iban_name_record_key' => 'iban_name',
        ]);

        $iban = $this->makeIban();
        $ibanName = 'fund_request_bank_account_organization_2';

        $fundRequest = $this->makeTestFundRequestAsBankAccount($identity, $fund, $iban, $ibanName);

        $fund->makeVoucher($identity, [
            'fund_request_id' => $fundRequest->id,
        ]);

        $this->assertBankAccountSearchIsScopedToOrganization(
            $organization1,
            $organization2,
            $identity,
            $iban,
            $ibanName,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReimbursementBankAccountQueryIsScopedToOrganization(): void
    {
        $organization1 = $this->makeTestOrganization($this->makeIdentity());
        $organization2 = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity($this->makeUniqueEmail(), $this->randomFakeBsn());

        $this->makeTestFund($organization1)->makeVoucher($identity);

        $fund = $this->makeTestFund($organization2, fundConfigsData: [
            'allow_reimbursements' => true,
        ]);

        $reimbursement = $this->makeReimbursement($fund->makeVoucher($identity), true);
        $iban = $this->makeIban();
        $ibanName = 'reimbursement_bank_account_organization_2';

        $reimbursement->update([
            'iban' => $iban,
            'iban_name' => $ibanName,
        ]);

        $reimbursement->assign($organization2->employees()->first())->approve();

        $this->assertBankAccountSearchIsScopedToOrganization(
            $organization1,
            $organization2,
            $identity,
            $iban,
            $ibanName,
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPayoutBankAccountQueryIsScopedToOrganization(): void
    {
        $organization1 = $this->makeTestOrganization($this->makeIdentity());
        $organization2 = $this->makeTestOrganization($this->makeIdentity());
        $identity = $this->makeIdentity($this->makeUniqueEmail(), $this->randomFakeBsn());

        $this->makeTestFund($organization1)->makeVoucher($identity);

        $voucher = $this->makeTestFund($organization2)->makeVoucher($identity, [
            'voucher_type' => Voucher::VOUCHER_TYPE_PAYOUT,
        ]);
        $iban = $this->makeIban();
        $ibanName = 'payout_bank_account_organization_2';

        $voucher->makeTransaction([
            'initiator' => VoucherTransaction::INITIATOR_SPONSOR,
            'target' => VoucherTransaction::TARGET_PAYOUT,
            'target_iban' => $iban,
            'target_name' => $ibanName,
            'amount' => 10,
        ]);

        $this->assertBankAccountSearchIsScopedToOrganization(
            $organization1,
            $organization2,
            $identity,
            $iban,
            $ibanName,
        );
    }

    /**
     * @return void
     */
    public function testFiltersByQueryProfileRecords(): void
    {
        $givenNamePart1 = 'unique';
        $givenNamePart2 = 'other';

        $familyNamePart1 = 'custom';
        $familyNamePart2 = 'common';

        $mobilePart1 = '444444';
        $mobilePart2 = '555555';

        $cityPart1 = 'first';
        $cityPart2 = 'second';

        $streetPart1 = 'primary';
        $streetPart2 = 'slave';

        $postalPart1 = '11111';
        $postalPart2 = '22222';

        $housePart1 = '80';
        $housePart2 = '90';

        $clientPart1 = 'shorter';
        $clientPart2 = 'longer';

        $municipalityNamePart1 = 'light';
        $municipalityNamePart2 = 'dark';

        $neighborhoodNamePart1 = 'morning';
        $neighborhoodNamePart2 = 'evening';

        $records1 = [
            'given_name' => "$givenNamePart1 name",
            'family_name' => "$familyNamePart1 family name",
            'mobile' => "{$mobilePart1}678",
            'city' => "$cityPart1 city",
            'street' => "$streetPart1 street",
            'house_number' => "{$housePart1}99",
            'postal_code' => "{$postalPart1}AB",
            'client_number' => "{$clientPart1}_number",
            'municipality_name' => "$municipalityNamePart1 municipality name",
            'neighborhood_name' => "$neighborhoodNamePart1 neighborhood name",
        ];

        $records2 = [
            'given_name' => "$givenNamePart2 name",
            'family_name' => "$familyNamePart2 family name",
            'mobile' => "{$mobilePart2}678",
            'city' => "$cityPart2 city",
            'street' => "$streetPart2 street",
            'house_number' => "{$housePart2}99",
            'postal_code' => "{$postalPart2}AB",
            'client_number' => "{$clientPart2}_number",
            'municipality_name' => "$municipalityNamePart2 municipality name",
            'neighborhood_name' => "$neighborhoodNamePart2 neighborhood name",
        ];

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity();
        $organization->findOrMakeProfile($identity1)->updateRecords($records1);

        $identity2 = $this->makeIdentity();
        $organization->findOrMakeProfile($identity2)->updateRecords($records2);

        $fund->makeVoucher($identity1);
        $fund->makeVoucher($identity2);

        $identity1RecordsMatch = [
            $givenNamePart1, $familyNamePart1, $mobilePart1, $cityPart1, $streetPart1, $postalPart1,
            $housePart1, $clientPart1, $municipalityNamePart1, $neighborhoodNamePart1,
        ];

        $identity2RecordsMatch = [
            $givenNamePart2, $familyNamePart2, $mobilePart2, $cityPart2, $streetPart2, $postalPart2,
            $housePart2, $clientPart2, $municipalityNamePart2, $neighborhoodNamePart2,
        ];

        foreach ($identity1RecordsMatch as $record) {
            $this->assertSearchIds([
                'q' => $record,
                'organization_id' => $organization->id,
            ], [$identity1->id]);
        }

        foreach ($identity2RecordsMatch as $record) {
            $this->assertSearchIds([
                'q' => $record,
                'organization_id' => $organization->id,
            ], [$identity2->id]);
        }
    }

    /**
     * @return void
     */
    public function testFiltersByExcludeId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity();
        $identity2 = $this->makeIdentity();

        $fund->makeVoucher($identity1);
        $fund->makeVoucher($identity2);

        $this->assertSearchIds([
            'exclude_id' => $identity2->id,
            'organization_id' => $organization->id,
        ], [$identity1->id]);

        $this->assertSearchIds([
            'exclude_id' => $identity1->id,
            'organization_id' => $organization->id,
        ], [$identity2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByHasBsn(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identityHasBsn = $this->makeIdentity($this->makeUniqueEmail(), bsn: $this->randomFakeBsn());
        $identityDoesntHaveBsn = $this->makeIdentity($this->makeUniqueEmail());

        $fund->makeVoucher($identityHasBsn);
        $fund->makeVoucher($identityDoesntHaveBsn);

        $this->assertSearchIds([
            'has_bsn' => true,
            'organization_id' => $organization->id,
        ], [$identityHasBsn->id]);

        $this->assertSearchIds([
            'has_bsn' => false,
            'organization_id' => $organization->id,
        ], [$identityDoesntHaveBsn->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByHouseholdId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity($this->makeUniqueEmail());
        $profile1 = $organization->findOrMakeProfile($identity1);
        $household1 = $this->makeTestHousehold($organization);
        $this->makeTestHouseholdProfile($household1, $profile1);

        $identity2 = $this->makeIdentity($this->makeUniqueEmail());
        $profile2 = $organization->findOrMakeProfile($identity2);
        $household2 = $this->makeTestHousehold($organization);
        $this->makeTestHouseholdProfile($household2, $profile2);

        $fund->makeVoucher($identity1);
        $fund->makeVoucher($identity2);

        // assert filter by household_id
        $this->assertSearchIds([
            'household_id' => $household1->id,
            'organization_id' => $organization->id,
        ], [$identity1->id]);

        $this->assertSearchIds([
            'household_id' => $household2->id,
            'organization_id' => $organization->id,
        ], [$identity2->id]);

        // assert filter by exclude_household_id
        $this->assertSearchIds([
            'exclude_household_id' => $household2->id,
            'organization_id' => $organization->id,
        ], [$identity1->id]);

        $this->assertSearchIds([
            'exclude_household_id' => $household1->id,
            'organization_id' => $organization->id,
        ], [$identity2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByExcludeRelationId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity($this->makeUniqueEmail());
        $profile1 = $organization->findOrMakeProfile($identity1);
        $relatedProfile1 = $organization->findOrMakeProfile($this->makeIdentity());

        $profile1->profile_relations()->firstOrCreate([
            'type' => ProfileRelation::TYPE_PARTNER,
            'subtype' => 'parent_child',
            'living_together' => true,
            'related_profile_id' => $relatedProfile1->id,
        ]);

        $identity2 = $this->makeIdentity($this->makeUniqueEmail());
        $profile2 = $organization->findOrMakeProfile($identity2);
        $relatedProfile2 = $organization->findOrMakeProfile($this->makeIdentity());

        $profile2->profile_relations()->firstOrCreate([
            'type' => ProfileRelation::TYPE_PARTNER,
            'subtype' => 'parent_child',
            'living_together' => true,
            'related_profile_id' => $relatedProfile2->id,
        ]);

        $fund->makeVoucher($identity1);
        $fund->makeVoucher($identity2);

        // assert filter by household_id
        $this->assertSearchIds([
            'exclude_relation_id' => $relatedProfile1->id,
            'organization_id' => $organization->id,
        ], [$identity2->id]);

        $this->assertSearchIds([
            'exclude_relation_id' => $relatedProfile2->id,
            'organization_id' => $organization->id,
        ], [$identity1->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByBirthDate(): void
    {
        $olderYear = 30;
        $newerYear = 20;

        $birthDate1 = Carbon::now()->subYears($olderYear)->format('Y-m-d');
        $birthDate2 = Carbon::now()->subYears($newerYear)->format('Y-m-d');

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity();
        $organization->findOrMakeProfile($identity1)->updateRecords(['birth_date' => $birthDate1]);

        $identity2 = $this->makeIdentity();
        $organization->findOrMakeProfile($identity2)->updateRecords(['birth_date' => $birthDate2]);

        $fund->makeVoucher($identity1);
        $fund->makeVoucher($identity2);

        $this->assertSearchIds([
            'birth_date_from' => Carbon::now()->subYears($olderYear + 1)->format('Y-m-d'),
            'organization_id' => $organization->id,
        ], [$identity1->id, $identity2->id]);

        $this->assertSearchIds([
            'birth_date_from' => Carbon::now()->subYears($olderYear)->addMonth()->format('Y-m-d'),
            'organization_id' => $organization->id,
        ], [$identity2->id]);

        $this->assertSearchIds([
            'birth_date_to' => Carbon::now()->subYears($newerYear - 1)->format('Y-m-d'),
            'organization_id' => $organization->id,
        ], [$identity1->id, $identity2->id]);

        $this->assertSearchIds([
            'birth_date_to' => Carbon::now()->subYears($newerYear)->subMonth()->format('Y-m-d'),
            'organization_id' => $organization->id,
        ], [$identity1->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByPostalCode(): void
    {
        $postalCode1 = '1111AB';
        $postalCode2 = '2222AC';

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity();
        $organization->findOrMakeProfile($identity1)->updateRecords(['postal_code' => $postalCode1]);

        $identity2 = $this->makeIdentity();
        $organization->findOrMakeProfile($identity2)->updateRecords(['postal_code' => $postalCode2]);

        $fund->makeVoucher($identity1);
        $fund->makeVoucher($identity2);

        $this->assertSearchIds([
            'postal_code' => $postalCode1,
            'organization_id' => $organization->id,
        ], [$identity1->id]);

        $this->assertSearchIds([
            'postal_code' => $postalCode2,
            'organization_id' => $organization->id,
        ], [$identity2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByCity(): void
    {
        $city1 = 'match';
        $city2 = 'other';

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity();
        $organization->findOrMakeProfile($identity1)->updateRecords(['city' => $city1]);

        $identity2 = $this->makeIdentity();
        $organization->findOrMakeProfile($identity2)->updateRecords(['city' => $city2]);

        $fund->makeVoucher($identity1);
        $fund->makeVoucher($identity2);

        $this->assertSearchIds([
            'city' => $city1,
            'organization_id' => $organization->id,
        ], [$identity1->id]);

        $this->assertSearchIds([
            'city' => $city2,
            'organization_id' => $organization->id,
        ], [$identity2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersByMunicipalityName(): void
    {
        $municipalityName1 = 'match';
        $municipalityName2 = 'other';

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity();
        $organization->findOrMakeProfile($identity1)->updateRecords(['municipality_name' => $municipalityName1]);

        $identity2 = $this->makeIdentity();
        $organization->findOrMakeProfile($identity2)->updateRecords(['municipality_name' => $municipalityName2]);

        $fund->makeVoucher($identity1);
        $fund->makeVoucher($identity2);

        $this->assertSearchIds([
            'municipality_name' => $municipalityName1,
            'organization_id' => $organization->id,
        ], [$identity1->id]);

        $this->assertSearchIds([
            'municipality_name' => $municipalityName2,
            'organization_id' => $organization->id,
        ], [$identity2->id]);
    }

    /**
     * @param string $filter
     * @return void
     */
    public function testFiltersBySessionActivity(string $filter = 'last_activity'): void
    {
        $now = Carbon::now();

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity();

        resolve('forus.session')->makeOrUpdateSession(
            '127.0.0.1',
            'sponsor',
            null,
            $this->makeIdentityProxy($identity1)->id,
            $identity1->address
        );

        Carbon::setTestNow(now()->addDays(5));

        $identity2 = $this->makeIdentity();

        resolve('forus.session')->makeOrUpdateSession(
            '127.0.0.1',
            'sponsor',
            null,
            $this->makeIdentityProxy($identity2)->id,
            $identity2->address
        );

        $fund->makeVoucher($identity1);
        $fund->makeVoucher($identity2);

        $this->assertSearchIds([
            "{$filter}_from" => $now->copy()->subDay()->format('Y-m-d'),
            'organization_id' => $organization->id,
        ], [$identity1->id, $identity2->id]);

        $this->assertSearchIds([
            "{$filter}_from" => $now->copy()->addDay()->format('Y-m-d'),
            'organization_id' => $organization->id,
        ], [$identity2->id]);

        $this->assertSearchIds([
            "{$filter}_to" => $now->copy()->subDay()->format('Y-m-d'),
            'organization_id' => $organization->id,
        ], []);

        $this->assertSearchIds([
            "{$filter}_to" => $now->copy()->addDays()->format('Y-m-d'),
            'organization_id' => $organization->id,
        ], [$identity1->id]);

        $this->assertSearchIds([
            "{$filter}_to" => $now->copy()->addDay(6)->format('Y-m-d'),
            'organization_id' => $organization->id,
        ], [$identity1->id, $identity2->id]);
    }

    /**
     * @return void
     */
    public function testFiltersBySessionLogin(): void
    {
        $this->testFiltersBySessionActivity('last_login');
    }

    /**
     * @return void
     */
    public function testOrdersByProfileRecords(): void
    {
        $records1 = [
            'given_name' => 'A given name',
            'family_name' => 'A family name',
            'mobile' => '1111678',
            'city' => 'A city',
            'street' => 'A street',
            'house_number' => '111199',
            'house_number_addition' => '11',
            'postal_code' => '1111AB',
            'client_number' => '11111',
            'birth_date' => '1980-01-01',
            'municipality_name' => 'A municipality name',
            'neighborhood_name' => 'A neighborhood name',
        ];

        $records2 = [
            'given_name' => 'B name',
            'family_name' => 'B family name',
            'mobile' => '22222678',
            'city' => 'B city',
            'street' => 'B street',
            'house_number' => '222299',
            'house_number_addition' => '22',
            'postal_code' => '22222AB',
            'client_number' => '2222222',
            'birth_date' => '2000-01-01',
            'municipality_name' => 'B municipality name',
            'neighborhood_name' => 'B neighborhood name',
        ];

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identityA = $this->makeIdentity();
        $organization->findOrMakeProfile($identityA)->updateRecords($records1);

        $identityB = $this->makeIdentity();
        $organization->findOrMakeProfile($identityB)->updateRecords($records2);

        $fund->makeVoucher($identityA);
        $fund->makeVoucher($identityB);

        $records = [
            'given_name', 'family_name', 'client_number', 'birth_date', 'city', 'street',
            'house_number', 'house_number_addition', 'postal_code', 'municipality_name',
            'neighborhood_name',
        ];

        foreach ($records as $record) {
            $this->assertSearchOrder([
                'organization_id' => $organization->id,
                'order_by' => $record,
                'order_dir' => 'asc',
            ], [$identityA->id, $identityB->id]);

            $this->assertSearchOrder([
                'organization_id' => $organization->id,
                'order_by' => $record,
                'order_dir' => 'desc',
            ], [$identityB->id, $identityA->id]);
        }
    }

    /**
     * @return void
     */
    public function testOrdersBySessionActivity(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identityA = $this->makeIdentity();

        resolve('forus.session')->makeOrUpdateSession(
            '127.0.0.1',
            'sponsor',
            null,
            $this->makeIdentityProxy($identityA)->id,
            $identityA->address
        );

        Carbon::setTestNow(now()->addDays(5));

        $identityB = $this->makeIdentity();

        resolve('forus.session')->makeOrUpdateSession(
            '127.0.0.1',
            'sponsor',
            null,
            $this->makeIdentityProxy($identityB)->id,
            $identityB->address
        );

        $fund->makeVoucher($identityA);
        $fund->makeVoucher($identityB);

        // assert order by last_activity
        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'last_activity',
            'order_dir' => 'asc',
        ], [$identityA->id, $identityB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'last_activity',
            'order_dir' => 'desc',
        ], [$identityB->id, $identityA->id]);

        // assert order by last_login
        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'last_login',
            'order_dir' => 'asc',
        ], [$identityA->id, $identityB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'last_login',
            'order_dir' => 'desc',
        ], [$identityB->id, $identityA->id]);

        // move to one day after and update session
        // assert that last_activity order was changed
        Carbon::setTestNow(now()->addDay());

        resolve('forus.session')->makeOrUpdateSession(
            '127.0.0.1',
            'sponsor',
            null,
            $this->makeIdentityProxy($identityA)->id,
            $identityA->address
        );

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'last_activity',
            'order_dir' => 'asc',
        ], [$identityB->id, $identityA->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'last_activity',
            'order_dir' => 'desc',
        ], [$identityA->id, $identityB->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAtOrId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identityA = $this->makeIdentity();

        Carbon::setTestNow(now()->addDays(5));

        $identityB = $this->makeIdentity();

        $fund->makeVoucher($identityA);
        $fund->makeVoucher($identityB);

        // assert order by id
        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'id',
            'order_dir' => 'asc',
        ], [$identityA->id, $identityB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'id',
            'order_dir' => 'desc',
        ], [$identityB->id, $identityA->id]);

        // assert order by created_at
        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$identityA->id, $identityB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$identityB->id, $identityA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByEmailOrBsn(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identityA = $this->makeIdentity($this->makeUniqueEmail('a_email'), 1111111);
        $identityB = $this->makeIdentity($this->makeUniqueEmail('b_email'), 2222222);

        $fund->makeVoucher($identityA);
        $fund->makeVoucher($identityB);

        // assert order by email
        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'email',
            'order_dir' => 'asc',
        ], [$identityA->id, $identityB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'email',
            'order_dir' => 'desc',
        ], [$identityB->id, $identityA->id]);

        // assert order by bsn
        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'bsn',
            'order_dir' => 'asc',
        ], [$identityA->id, $identityB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'bsn',
            'order_dir' => 'desc',
        ], [$identityB->id, $identityA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByType(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $identityA = $this->makeIdentity(type: Identity::TYPE_PROFILE);
        $identityB = $this->makeIdentity(type: Identity::TYPE_EMPLOYEE);

        $fund->makeVoucher($identityA);
        $fund->makeVoucher($identityB);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'type',
            'order_dir' => 'asc',
        ], [$identityA->id, $identityB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'type',
            'order_dir' => 'desc',
        ], [$identityB->id, $identityA->id]);
    }

    /**
     * @param Organization $organization
     * @param array $assertData
     * @return void
     */
    private function assertBankAccountSearch(Organization $organization, array $assertData): void
    {
        foreach ($assertData as $data) {
            // assert by bank account name
            $this->assertSearchIds([
                'q' => $data['iban_name'],
                'organization_id' => $organization->id,
            ], [$data['identity']->id]);

            // assert by bank account iban
            $this->assertSearchIds([
                'q' => $data['iban'],
                'organization_id' => $organization->id,
            ], [$data['identity']->id]);
        }
    }

    /**
     * @param Organization $organization
     * @param Organization $bankAccountOrganization
     * @param Identity $identity
     * @param string $iban
     * @param string $ibanName
     * @return void
     */
    private function assertBankAccountSearchIsScopedToOrganization(
        Organization $organization,
        Organization $bankAccountOrganization,
        Identity $identity,
        string $iban,
        string $ibanName,
    ): void {
        $this->assertSearchIds([
            'organization_id' => $organization->id,
        ], [$identity->id]);

        $this->assertBankAccountSearch($bankAccountOrganization, [[
            'iban' => $iban,
            'iban_name' => $ibanName,
            'identity' => $identity,
        ]]);

        $this->assertSearchIds([
            'q' => $ibanName,
            'organization_id' => $organization->id,
        ], []);

        $this->assertSearchIds([
            'q' => $iban,
            'organization_id' => $organization->id,
        ], []);
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param string $iban
     * @param string $ibanName
     * @return FundRequest
     */
    private function makeTestFundRequestAsBankAccount(
        Identity $identity,
        Fund $fund,
        string $iban,
        string $ibanName
    ): FundRequest {
        $employee = $fund->organization->employees()->first();
        $fundRequest = $this->makeFundRequestForIdentity($fund, $identity);

        $fundRequest->records()->create([
            'value' => $iban,
            'record_type_key' => 'iban',
            'source' => FundRequestRecord::SOURCE_FORM,
        ]);

        $fundRequest->records()->create([
            'value' => $ibanName,
            'record_type_key' => 'iban_name',
            'source' => FundRequestRecord::SOURCE_FORM,
        ]);

        $fundRequest->assignEmployee($employee)->approve();

        return $fundRequest;
    }

    /**
     * @param array $filters
     * @return IdentitiesSearch
     */
    private function makeSearch(array $filters): IdentitiesSearch
    {
        return new IdentitiesSearch($filters, Identity::query());
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters);
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @return void
     */
    private function assertSearchOrder(array $filters, array $expectedIds): void
    {
        $search = $this->makeSearch($filters);
        $actual = $search->query()->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
