<?php

namespace Tests\Feature;

use App\Models\Implementation;
use App\Models\Organization;
use App\Models\OrganizationContact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Tests\Traits\VoucherTestTrait;
use Throwable;

class OrganizationContactsTest extends TestCase
{
    use VoucherTestTrait;
    use DatabaseTransactions;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/platform/organizations/%s';

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdateOrganizationContactsSuccess(): void
    {
        $contacts = [[
            'value' => 'lorem@example.com',
            'key' => OrganizationContact::KEY_PROVIDER_APPLIED,
        ], [
            'value' => 'lorem2@example.com',
            'key' => OrganizationContact::KEY_FUND_BALANCE_LOW_EMAIL,
        ], [
            'value' => 'lorem3@example.com',
            'key' => OrganizationContact::KEY_BANK_CONNECTION_EXPIRING,
        ]];

        foreach ([
            '1234', str_repeat('1', 20), '+31 (0)6 1234-5678', '+31+612345678',
        ] as $phone) {
            $this->doUpdateOrganizationContacts($contacts, $phone);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdateOrganizationContactsFail(): void
    {
        $contacts = [[
            'value' => 'lorem-example.com',
            'key' => OrganizationContact::KEY_PROVIDER_APPLIED,
        ], [
            'value' => 'lorem2-example.com',
            'key' => OrganizationContact::KEY_FUND_BALANCE_LOW_EMAIL,
        ], [
            'value' => 'lorem3-example.com',
            'key' => OrganizationContact::KEY_BANK_CONNECTION_EXPIRING,
        ]];

        $attribute = trans('validation.attributes.phone');
        $formatError = trans('validation.regex', compact('attribute'));

        foreach ([
            ['invalid_phone', [$formatError]],
            ['020.123.4567', [$formatError]],
            ['123', [
                trans('validation.min.string', ['attribute' => $attribute, 'min' => 4]),
                $formatError,
            ]],
            [str_repeat('1', 21), [
                trans('validation.max.string', ['attribute' => $attribute, 'max' => 20]),
                $formatError,
            ]],
        ] as [$phone, $errors]) {
            $response = $this->doUpdateOrganizationContacts($contacts, $phone, false);

            $this->assertSame($errors, $response->json('errors.phone'));
        }
    }

    /**
     * @param array $contacts
     * @param string $phone
     * @param bool $success
     * @return \Illuminate\Testing\TestResponse|void
     */
    protected function doUpdateOrganizationContacts(array $contacts, string $phone, bool $success = true)
    {
        $organization = Organization::whereHas('funds')->first();
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity), [
            'client_type' => Implementation::FRONTEND_SPONSOR_DASHBOARD,
        ]);

        $response = $this->patchJson($this->getApiUrl($organization), compact('contacts', 'phone'), $headers);

        if (!$success) {
            return $response->assertJsonValidationErrors([
                'phone',
                'contacts.0.value',
                'contacts.1.value',
                'contacts.2.value',
            ]);
        }

        $response->assertSuccessful();
        $this->assertSame($phone, $response->json('data.phone'));
        $this->assertSame($phone, $organization->refresh()->phone);
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
