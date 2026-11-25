<?php

namespace Tests\Feature;

use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Profile;
use App\Models\ProfileRecordGroup;
use App\Models\RecordType;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\App;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestRecords;
use Throwable;

class ProfileRecordTypesTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFunds;
    use MakesTestOrganizations;
    use MakesTestRecords;

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterCanEditEnabledContactFields(): void
    {
        $this->freezeTime();
        [$organization, $implementation, $identity, $profile, $type] = $this->makeRequesterProfileFixture();
        $other = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles' => true]);
        $otherProfile = $other->findOrMakeProfile($identity);
        $untouched = $this->makeProfileRecord($otherProfile, $type, 'Other city');
        $this->makeProfileRecord($profile, $type, 'Old city');
        $headers = ['Client-Key' => $implementation->key];
        $this->travel(1)->seconds();

        $this->apiUpdateProfileRequest($identity, ['city' => 'New city'], $headers)
            ->assertOk()
            ->assertJsonPath('records.city.0.value', 'New city');

        $this->apiGetProfileRequest($identity, $headers)
            ->assertOk()
            ->assertJsonPath('records.city.0.value', 'New city')
            ->assertJsonPath('records.city.1.value', 'Old city');

        $records = $profile->profile_records()->where('record_type_id', $type->id)->get();

        $this->assertSame(['New city', 'Old city'], $records->pluck('value')->all());
        $this->assertNull($records->first()->employee_id);
        $this->assertSame($organization->id, $profile->organization_id);
        $this->assertSame([$untouched->id], $otherProfile->profile_records()->pluck('id')->all());
        $this->assertSame('Other city', $untouched->refresh()->value);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterCannotEditDisabledOrSponsorManagedContactFields(): void
    {
        [, $implementation, $identity, $profile, $type] = $this->makeRequesterProfileFixture();
        $this->makeProfileRecord($profile, $type, 'Keep city');
        $before = $profile->profile_records()->get()->toArray();

        foreach (['profile_records', 'profile_requester_editable'] as $flag) {
            $type->update([$flag => false]);

            $this
                ->apiUpdateProfileRequest($identity, ['city' => 'Must not save'], ['Client-Key' => $implementation->key])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('city');

            $this->assertSame($before, $profile->profile_records()->get()->toArray());
            $type->update([$flag => true]);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testDashboardCatalogExposesProfileGroupConfiguration(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles' => true]);
        $other = $this->makeTestOrganization($this->makeIdentity());
        $group = ProfileRecordGroup::create(['key' => 'c05_group_' . $organization->id, 'sort_order' => 60]);
        $group->translateOrNew(app()->getLocale())->name = 'Test group';
        $group->save();

        $global = $this->makeProfileRecordType(attributes: [
            'profile_record_group_id' => $group->id, 'profile_sort_order' => 40,
        ]);

        $global->translateOrNew(app()->getLocale())->name = 'Global field';
        $global->save();

        $own = $this->makeProfileRecordType($organization, [
            'profile_record_group_id' => $group->id, 'profile_sort_order' => 20,
        ]);

        $ungrouped = $this->makeProfileRecordType($organization);
        $foreign = $this->makeProfileRecordType($other);

        $response = $this->apiGetRecordTypesRequest($organization->identity, [
            'organization_id' => $organization->id, 'profile_records' => 1,
        ], ['Client-Type' => Implementation::FRONTEND_SPONSOR_DASHBOARD])->assertOk();

        $types = collect($response->json())->keyBy('key');

        $this->assertSame('Global field', $types[$global->key]['name']);
        $this->assertSame(40, $types[$global->key]['profile_sort_order']);
        $this->assertSame(20, $types[$own->key]['profile_sort_order']);
        $this->assertSame($organization->id, $types[$own->key]['organization_id']);

        $this->assertSame([
            'id' => $group->id, 'key' => $group->key, 'sort_order' => 60, 'name' => 'Test group',
        ], $types[$global->key]['profile_record_group']);

        $this->assertSame($group->id, $types[$own->key]['profile_record_group']['id']);
        $this->assertNull($types[$ungrouped->key]['profile_record_group']);
        $this->assertFalse($types->has($foreign->key));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testDisabledRecordTypeKeepsSavedProfileHistoryReadable(): void
    {
        $this->freezeTime();
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles' => true]);
        $identity = $this->makeIdentity(type: Identity::TYPE_PROFILE, organizationId: $organization->id);
        $profile = $organization->findOrMakeProfile($identity);
        $type = $this->makeProfileRecordType($organization);
        $old = $this->makeProfileRecord($profile, $type, 'Old value');
        $this->travel(1)->seconds();
        $current = $this->makeProfileRecord($profile, $type, 'Current value');
        $type->update(['profile_records' => false]);

        $response = $this
            ->apiGetRecordTypesRequest($organization->identity, [
                'organization_id' => $organization->id, 'profile_records' => 1,
            ], ['Client-Type' => Implementation::FRONTEND_SPONSOR_DASHBOARD])
            ->assertOk();

        $this->assertNotContains($type->key, array_column($response->json(), 'key'));

        $this
            ->apiViewIdentityRequest($organization->id, $identity->id, $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.records.' . $type->key . '.0.id', $current->id)
            ->assertJsonPath('data.records.' . $type->key . '.0.value', 'Current value')
            ->assertJsonPath('data.records.' . $type->key . '.1.id', $old->id)
            ->assertJsonPath('data.records.' . $type->key . '.1.value', 'Old value');
    }

    /**
     * @throws Throwable
     * @return array{Organization, Implementation, Identity, Profile, RecordType}
     */
    private function makeRequesterProfileFixture(): array
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_profiles' => true]);
        $implementation = $this->makeTestImplementation($organization);
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $profile = $organization->findOrMakeProfile($identity);
        $type = RecordType::findByKey('city');
        $type->update(['profile_records' => true, 'profile_requester_editable' => true, 'organization_id' => null]);

        return [$organization, $implementation, $identity, $profile, $type];
    }
}
