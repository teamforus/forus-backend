<?php

namespace Tests\Feature;

use App\Models\DemoTransaction;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\IdentityEmail;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Record;
use App\Models\RecordType;
use App\Models\Role;
use App\Models\Voucher;
use Illuminate\Support\Str;
use Tests\TestCase;

class BaseRoutersTest extends TestCase
{
    /**
     * @return void
     * @throws \Throwable
     */
    public function testPlatformRoutes(): void
    {
        //platform/provider/transactions
        $identity = $this->makeIdentity();
        $proxy = $this->makeIdentityProxy($identity);

        $response = $this->getJson(
            '/api/v1/platform/provider/transactions',
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        //platform/devices/register-push
        $identity = $this->makeIdentity();
        $proxy = $this->makeIdentityProxy($identity);

        $response = $this->post(
            '/api/v1/platform/devices/register-push',
            ['id' => Str::random()],
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        //platform/devices/delete-push
        $identity = $this->makeIdentity();
        $proxy = $this->makeIdentityProxy($identity);

        $response = $this->delete(
            '/api/v1/platform/devices/delete-push',
            ['id' => Str::random()],
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        //platform/employees?role=validation
        $identity = $this->makeIdentity();
        $proxy = $this->makeIdentityProxy($identity);
        $key = Role::first()->key;

        $response = $this->getJson(
            "/api/v1/platform/employees?role=$key",
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        $response = $this->getJson(
            "/api/v1/platform/employees?role=test_$key",
            $this->makeApiHeaders($proxy)
        );

        $response->assertJsonValidationErrorFor('role');

        //platform/demo/transactions/{token}
        $identity = $this->makeIdentity();
        $proxy = $this->makeIdentityProxy($identity);

        $response = $this->post(
            '/api/v1/platform/demo/transactions',
            [],
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        /** @var DemoTransaction $demo */
        $demo = DemoTransaction::find($response->json('data.id'));
        $this->assertNotNull($demo);

        $response = $this->getJson(
            "/api/v1/platform/demo/transactions/$demo->token",
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();
    }

    /**
     * @return void
     */
    public function testPlatformVouchersRoutes(): void
    {
        //platform/provider/vouchers/{address}
        /** @var Organization $organization */
        $organization = Organization::has('funds.vouchers')->first();
        $this->assertNotNull($organization);

        $identity = $organization->identity;
        $this->assertNotNull($identity);

        /** @var Fund $fund */
        $fund = $organization->funds->first();
        $this->assertNotNull($fund);

        /** @var Voucher $voucher */
        $voucher = $fund->vouchers->first();
        $this->assertNotNull($voucher);

        $proxy = $this->makeIdentityProxy($identity);
        $voucherToken = $voucher->token_without_confirmation->address;

        $response = $this->getJson(
            "/api/v1/platform/provider/vouchers/$voucherToken",
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        //platform/provider/vouchers/{address}/product-vouchers
        $response = $this->getJson(
            "/api/v1/platform/provider/vouchers/$voucherToken/product-vouchers",
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        //platform/provider/vouchers/{address}/products
        $response = $this->getJson(
            "/api/v1/platform/provider/vouchers/$voucherToken/products",
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        //platform/vouchers
        $response = $this->getJson('/api/v1/platform/vouchers', $this->makeApiHeaders($proxy));
        $response->assertSuccessful();

        //platform/vouchers/{address}
        $response = $this->getJson(
            "/api/v1/platform/vouchers/$voucherToken",
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        $response = $this->getJson(
            "/api/v1/platform/vouchers/$voucher->number",
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        //platform/vouchers/{address}/send-email
        $response = $this->post(
            "/api/v1/platform/vouchers/$voucherToken/send-email",
            [],
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        $response = $this->post(
            "/api/v1/platform/vouchers/$voucher->number/send-email",
            [],
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();
    }

    /**
     * @return void
     */
    public function testIdentityRoutes()
    {
        // identity/proxy/email
        /** @var IdentityEmail $email */
        $email = IdentityEmail::first();

        $response = $this->postJson(
            '/api/v1/identity/proxy/email',
            ['email' => $email->email, 'source' => Implementation::keysAvailable()->first()]
        );

        $response->assertSuccessful();

        //identity/proxy/short-token
        $identity = $this->makeIdentity();
        $proxy = $this->makeIdentityProxy($identity);

        $response = $this->post(
            '/api/v1/identity/proxy/short-token', [], $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        //identity/proxy/token
        $response = $this->post('/api/v1/identity/proxy/token');
        $response->assertSuccessful();

        //identity/proxy/code
        $response = $this->postJson('/api/v1/identity/proxy/code');
        $response->assertSuccessful();

        //identity/record-validations
        $identity = $this->makeIdentity();
        $proxy = $this->makeIdentityProxy($identity);
        $record_id = Record::first()?->id;

        $response = $this->postJson(
            '/api/v1/identity/record-validations',
            compact('record_id'),
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        //identity
        $email = $this->makeUniqueEmail();
        $response = $this->postJson('/api/v1/identity', compact('email'));
        $response->assertSuccessful();

        //identity/records/
        $identity = $this->makeIdentity();
        $proxy = $this->makeIdentityProxy($identity);
        $response = $this->getJson(
            '/api/v1/identity/records',
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        //identity/records/  POST
        $identity = $this->makeIdentity();
        $proxy = $this->makeIdentityProxy($identity);
        /** @var RecordType $type */
        $type = RecordType::where('type', 'string')->where('system', false)->first();
        $this->assertNotNull($type);

        $response = $this->post(
            '/api/v1/identity/records',
            ['type' => $type->key, 'value' => 'string'],
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        //identity/records/{id}
        $identity = $this->makeIdentity();
        $proxy = $this->makeIdentityProxy($identity);
        $type = RecordType::where('type', 'string')->where('system', false)->first();
        $this->assertNotNull($type);
        $record = $identity->makeRecord($type, 'string');
        $this->assertNotNull($record);

        $response = $this->getJson(
            "/api/v1/identity/records/$record->id",
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        //identity/record-types
        $identity = $this->makeIdentity();
        $proxy = $this->makeIdentityProxy($identity);
        $response = $this->getJson('/api/v1/identity/records', $this->makeApiHeaders($proxy));
        $response->assertSuccessful();

        //identity/proxy/authorize/token
        $identity = $this->makeIdentity();
        $proxy = $this->makeIdentityProxy($identity);
        $proxyExchange = Identity::makeProxy('qr_code');

        $response = $this->post(
            '/api/v1/identity/proxy/authorize/token',
            ['auth_token' => $proxyExchange->exchange_token],
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        //identity/proxy/email/exchange/{emailToken}
        $identity = $this->makeIdentity();
        $proxy = $this->makeIdentityProxy($identity);
        $proxyExchange = $this->makeIdentityProxy($identity, false, 'email_code');

        $response = $this->getJson(
            "/api/v1/identity/proxy/email/exchange/$proxyExchange->exchange_token",
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        //identity/proxy/confirmation/exchange/
        $identity = $this->makeIdentity();
        $proxy = $this->makeIdentityProxy($identity);
        $proxyExchange = $this->makeIdentityProxy($identity, false);

        $response = $this->getJson(
            '/api/v1/identity/proxy/confirmation/exchange/' . $proxyExchange->exchange_token,
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        //identity/record-validations/{code}
        $identity = $this->makeIdentity();
        $proxy = $this->makeIdentityProxy($identity);

        $type = RecordType::where('type', 'string')->where('system', false)->first();
        $this->assertNotNull($type);
        $record = $identity->makeRecord($type, 'string');
        $this->assertNotNull($record);
        $recordValidation = $record->makeValidationRequest();
        $this->assertNotNull($recordValidation);

        $response = $this->getJson(
            "/api/v1/identity/record-validations/$recordValidation->uuid",
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();

        //identity/record-validations/{code}/approve
        $identity = $this->makeIdentity();
        $proxy = $this->makeIdentityProxy($identity);

        $type = RecordType::where('type', 'string')->where('system', false)->first();
        $this->assertNotNull($type);
        $record = $identity->makeRecord($type, 'string');
        $this->assertNotNull($record);
        $recordValidation = $record->makeValidationRequest();
        $this->assertNotNull($recordValidation);

        $response = $this->patch(
            "/api/v1/identity/record-validations/$recordValidation->uuid/approve",
            [],
            $this->makeApiHeaders($proxy)
        );

        $response->assertSuccessful();
    }
}
