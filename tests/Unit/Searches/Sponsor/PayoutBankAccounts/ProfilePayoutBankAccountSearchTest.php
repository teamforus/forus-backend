<?php

namespace Tests\Unit\Searches\Sponsor\PayoutBankAccounts;

use App\Models\Organization;
use App\Searches\Sponsor\PayoutBankAccounts\ProfilePayoutBankAccountSearch;
use App\Traits\DoesTesting;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Tests\Unit\Searches\SearchTestCase;

class ProfilePayoutBankAccountSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestVouchers;
    use MakesTestFundRequests;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity, ['bsn_enabled' => true]);

        $search = new ProfilePayoutBankAccountSearch($organization, []);

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @return void
     */
    public function testFiltersByIdentityId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $organization->forceFill(['allow_profiles' => true])->save();

        $identity1 = $this->makeIdentity($this->makeUniqueEmail());
        $profile1 = $organization->findOrMakeProfile($identity1);

        $profileBankAccount1 = $profile1->profile_bank_accounts()->create([
            'iban' => $this->makeIban(),
            'name' => $this->makeIbanName(),
        ]);

        $identity2 = $this->makeIdentity($this->makeUniqueEmail());
        $profile2 = $organization->findOrMakeProfile($identity2);

        $this->assertSearchIds([], [$profileBankAccount1->id], $organization);

        $profileBankAccount2 = $profile2->profile_bank_accounts()->create([
            'iban' => $this->makeIban(),
            'name' => $this->makeIbanName(),
        ]);

        $this->assertSearchIds([], [$profileBankAccount1->id, $profileBankAccount2->id], $organization);

        $this->assertSearchIds([
            'identity_id' => $identity1->id,
        ], [$profileBankAccount1->id], $organization);

        $this->assertSearchIds([
            'identity_id' => $identity2->id,
        ], [$profileBankAccount2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByQuery(): void
    {
        $bsnPart1 = '12345';
        $bsnPart2 = '45678';

        $emailPart1 = 'f_anywhere_not_used_email';
        $emailPart2 = 's_not_used_anywhere_email';

        $namePart1 = 'first';
        $namePart2 = 'last';

        $ibanPart1 = '44444';
        $ibanPart2 = '66666';

        $organization = $this->makeTestOrganization($this->makeIdentity(), [
            'bsn_enabled' => true,
            'allow_profiles' => true,
        ]);

        $identity1 = $this->makeIdentity($this->makeUniqueEmail($emailPart1), "{$bsnPart1}9999");
        $identity2 = $this->makeIdentity($this->makeUniqueEmail($emailPart2), "{$bsnPart2}8888");

        $profile1 = $organization->findOrMakeProfile($identity1);
        $profile2 = $organization->findOrMakeProfile($identity2);

        $profileBankAccount1 = $profile1->profile_bank_accounts()->create([
            'iban' => "NL{$ibanPart1}55555",
            'name' => "$namePart1 name",
        ]);

        $profileBankAccount2 = $profile2->profile_bank_accounts()->create([
            'iban' => "NL{$ibanPart2}55555",
            'name' => "$namePart2 name",
        ]);

        // assert filter by q for first bank account
        $this->assertSearchIds([
            'q' => $bsnPart1,
        ], [$profileBankAccount1->id], $organization);

        $this->assertSearchIds([
            'q' => $emailPart1,
        ], [$profileBankAccount1->id], $organization);

        $this->assertSearchIds([
            'q' => $namePart1,
        ], [$profileBankAccount1->id], $organization);

        $this->assertSearchIds([
            'q' => $ibanPart1,
        ], [$profileBankAccount1->id], $organization);

        // assert filter by q for second bank account
        $this->assertSearchIds([
            'q' => $bsnPart2,
        ], [$profileBankAccount2->id], $organization);

        $this->assertSearchIds([
            'q' => $emailPart2,
        ], [$profileBankAccount2->id], $organization);

        $this->assertSearchIds([
            'q' => $namePart2,
        ], [$profileBankAccount2->id], $organization);

        $this->assertSearchIds([
            'q' => $ibanPart2,
        ], [$profileBankAccount2->id], $organization);
    }

    /**
     * @param array $filters
     * @param Organization $organization
     * @return ProfilePayoutBankAccountSearch
     */
    private function makeSearch(array $filters, Organization $organization): ProfilePayoutBankAccountSearch
    {
        return new ProfilePayoutBankAccountSearch($organization, $filters);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Organization $organization
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds, Organization $organization): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters, $organization);
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }
}
