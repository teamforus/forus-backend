<?php

namespace Tests\Traits;

use App\Models\Organization;
use App\Models\Profile;
use App\Models\RecordType;
use App\Modules\DCP\Enums\DCPEnvironment;
use App\Modules\DCP\Models\DCPConnection;
use App\Modules\DCP\Models\DCPOrder;
use App\Modules\VoucherPass\Models\VoucherPass;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Random\RandomException;
use Throwable;

trait MakesTestDCPOrders
{
    use MakesTestRecords;
    use MakesTestVoucherPasses;

    /**
     * @throws Throwable
     * @return array{
     *     organization: Organization, connection: DCPConnection, pass: VoucherPass, owner: Profile, recipient: Profile
     * }
     */
    protected function makeDCPFixture(): array
    {
        Http::preventStrayRequests();
        Config::set('dcp.enabled', true);
        Config::set('dcp.environments.sandbox', 'https://dcp.test');

        ['organization' => $organization, 'owner' => $ownerIdentity, 'target' => $recipientIdentity] =
            $this->makeVoucherPassFixture();

        $connection = $this->makeDCPConnection($organization);
        $organization->forceFill(['dcp_connection_id' => $connection->id])->save();
        $owner = $organization->findOrMakeProfile($ownerIdentity);
        $recipient = $organization->findOrMakeProfile($recipientIdentity);

        $this->setDCPProfileValues($owner, [
            'given_name' => 'Taylor',
            'initials' => 'T.O.',
            'family_name_prefix' => 'van',
            'family_name' => 'Owner',
            'birth_date' => '1990-04-12',
            'municipality_code' => '2020',
        ]);

        $this->setDCPProfileValues($recipient, [
            'given_name' => 'Robin',
            'initials' => 'R.R.',
            'family_name_prefix' => 'de',
            'family_name' => 'Recipient',
            'birth_date' => '1980-09-23',
            'municipality_code' => '2021',
            'street' => 'Residential Road',
            'house_number' => '10',
            'postal_code' => '1234 ab',
            'city' => 'Duiven',
        ]);

        $pass = VoucherPass::create([
            'organization_id' => $organization->id,
            'owner_identity_id' => $ownerIdentity->id,
            'target_identity_id' => $recipientIdentity->id,
            'external_id' => 'print-pass-1',
            'code' => VoucherPass::generateCode(),
            'print_label' => 'Family budget',
            'state' => VoucherPass::STATE_ACTIVE,
            'print_requested' => true,
        ]);

        return compact('organization', 'connection', 'pass', 'owner', 'recipient');
    }

    /**
     * @param Organization $organization
     * @return DCPConnection
     */
    protected function makeDCPConnection(Organization $organization): DCPConnection
    {
        return DCPConnection::create([
            'implementation_id' => $this->makeTestImplementation($organization)->id,
            'environment' => DCPEnvironment::SANDBOX,
            'enabled' => true,
            'username' => 'fixture-user',
            'password' => 'fixture-password',
        ]);
    }

    /**
     * @param Profile $profile
     * @param array<string, string> $values
     * @param array $attributes
     * @return void
     */
    protected function setDCPProfileValues(Profile $profile, array $values, array $attributes = []): void
    {
        foreach ($values as $key => $value) {
            $type = RecordType::firstOrCreate(['key' => $key, 'organization_id' => null], [
                'type' => $key === 'birth_date' ? RecordType::TYPE_DATE : RecordType::TYPE_STRING,
            ]);

            $profile->profile_records()->where('record_type_id', $type->id)->delete();
            $this->makeProfileRecord($profile, $type, $value, $attributes);
        }
    }

    /**
     * @param VoucherPass $pass
     * @return DCPOrder
     */
    protected function prepareDCPOrder(VoucherPass $pass): DCPOrder
    {
        $organization = $pass->organization;

        $response = $this
            ->apiPrepareVoucherPassDCPRequest($organization, ['pass_ids' => [$pass->id]], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'created');

        return DCPOrder::findOrFail($response->json('data.0.order_id'));
    }

    /**
     * @param VoucherPass $pass
     * @param array $attributes
     * @throws RandomException
     * @return VoucherPass
     */
    protected function makeAdditionalDCPPass(VoucherPass $pass, array $attributes = []): VoucherPass
    {
        $additional = $pass->replicate();

        $additional
            ->fill([
                'external_id' => null,
                'code' => VoucherPass::generateCode(),
                ...$attributes,
            ])
            ->save();

        return $additional;
    }
}
