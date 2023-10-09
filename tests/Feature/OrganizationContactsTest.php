<?php

namespace Tests\Feature;

use App\Models\Implementation;
use App\Models\Organization;
use App\Models\OrganizationContact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Tests\Traits\VoucherTestTrait;

class OrganizationContactsTest extends TestCase
{
    use VoucherTestTrait, DatabaseTransactions;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/organizations/%s';

    /**
     * @return void
     * @throws \Throwable
     */
    public function testUpdateOrganizationContactsSuccess(): void
    {
        $this->doUpdateOrganizationContacts([[
            'value' => 'lorem@example.com',
            'key' => OrganizationContact::KEY_PROVIDER_APPLIED,
        ], [
            'value' => 'lorem2@example.com',
            'key' => OrganizationContact::KEY_FUND_BALANCE_LOW_EMAIL,
        ], [
            'value' => 'lorem3@example.com',
            'key' => OrganizationContact::KEY_BANK_CONNECTION_EXPIRING,
        ]]);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testUpdateOrganizationContactsFail(): void
    {
        $this->doUpdateOrganizationContacts([[
            'value' => 'lorem-example.com',
            'key' => OrganizationContact::KEY_PROVIDER_APPLIED,
        ], [
            'value' => 'lorem2-example.com',
            'key' => OrganizationContact::KEY_FUND_BALANCE_LOW_EMAIL,
        ], [
            'value' => 'lorem3-example.com',
            'key' => OrganizationContact::KEY_BANK_CONNECTION_EXPIRING,
        ]], false);
    }

    /**
     * @param array $contacts
     * @param bool $success
     * @return \Illuminate\Testing\TestResponse|void
     */
    protected function doUpdateOrganizationContacts(array $contacts, bool $success = true)
    {
        $organization = Organization::whereHas('funds')->first();
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity), [
            'client_type' => Implementation::FRONTEND_SPONSOR_DASHBOARD,
        ]);

        $response = $this->patchJson($this->getApiUrl($organization), compact('contacts'), $headers);

        if (!$success) {
            return $response->assertJsonValidationErrors([
                'contacts.0.value',
                'contacts.1.value',
                'contacts.2.value',
            ]);
        }

        $response->assertSuccessful();
        $resContacts = Arr::keyBy($response->json('data.contacts'), 'key');

        foreach ($contacts as $contact) {
            $this->assertEquals($contact['value'], $resContacts[$contact['key']]['value']);
            $this->assertEquals($contact['value'], $organization->getContact($contact['key']));
        }
    }

    /**
     * @param Organization $organization
     * @param string $append
     * @return string
     */
    protected function getApiUrl(Organization $organization, string $append = ''): string
    {
        return sprintf($this->apiUrl, $organization->id) . $append;
    }
}
