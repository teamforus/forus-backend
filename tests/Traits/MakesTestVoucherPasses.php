<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Modules\VoucherPass\Models\VoucherPassType;
use Throwable;

trait MakesTestVoucherPasses
{
    use MakesTestFunds;
    use MakesTestRequesterImports;

    /**
     * @throws Throwable
     * @return array{organization: Organization, fund: Fund, type: VoucherPassType, owner: Identity, target: Identity}
     */
    protected function makeVoucherPassFixture(): array
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
            'is_sponsor' => true,
            'allow_voucher_passes' => true,
            'allow_profiles' => true,
            'bsn_enabled' => true,
        ]);

        $fund = $this->makeTestFund($organization);
        $type = $this->makeVoucherPassType($organization, 'Family', [$fund->id]);
        $owner = $this->makeVoucherPassParty($organization);
        $target = $this->makeVoucherPassParty($organization);

        return compact('organization', 'fund', 'type', 'owner', 'target');
    }

    /**
     * @param Organization $organization
     * @throws Throwable
     * @return Identity
     */
    protected function makeVoucherPassParty(Organization $organization): Identity
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail(), $this->makeUniqueBsn());
        $profile = $organization->findOrMakeProfile($identity);
        $row = $this->makeRequesterImportRow($organization, ['bsn' => $identity->bsn]);

        $row->update(['profile_id' => $profile->id]);

        return $identity;
    }

    /**
     * @param Organization $organization
     * @param string $name
     * @param array<int, int> $fundIds
     * @return VoucherPassType
     */
    protected function makeVoucherPassType(Organization $organization, string $name, array $fundIds): VoucherPassType
    {
        $type = $organization->voucher_pass_types()->create(['name' => $name]);

        $type->funds()->attach($fundIds);

        return $type;
    }

    /**
     * @param Identity $owner
     * @param Identity $target
     * @param array $attributes
     * @return array
     */
    protected function makeVoucherPassData(Identity $owner, Identity $target, array $attributes = []): array
    {
        return [
            'external_id' => 'pass-1',
            'owner_bsn' => $owner->bsn,
            'target_bsn' => $target->bsn,
            'type_names' => ['Family'],
            'fund_ids' => [],
            'print_label' => 'Family budget',
            'state' => 'active',
            'print_requested' => true,
            ...$attributes,
        ];
    }
}
