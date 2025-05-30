<?php

namespace Tests\Feature;

use App\Models\DemoTransaction;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Role;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\MakesTestFundProviders;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class MeAppBasicRoutersTest extends TestCase
{
    use MakesTestFunds;
    use MakesTestOrganizations;
    use MakesTestFundProviders;

    /**
     * @throws Throwable
     * @return void
     */
    public function testPushNotificationTokenEndpoints(): void
    {
        $identity = $this->makeIdentity();
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($identity));
        $pushId = Str::random();

        //platform/devices/register-push
        $this->postJson('/api/v1/platform/devices/register-push', ['id' => $pushId], $headers)->assertSuccessful();
        $this->assertNotEmpty($identity->notification_tokens()->where('token', $pushId)->first());

        //platform/devices/delete-push
        $this->deleteJson('/api/v1/platform/devices/delete-push', ['id' => $pushId], $headers)->assertSuccessful();
        $this->assertEmpty($identity->notification_tokens()->where('token', $pushId)->first());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderTransactionsEmployeesAndDemoTransactionsRoutes(): void
    {
        $identity = $this->makeIdentity();
        $headers = $this->makeApiHeaders($this->makeIdentityProxy($identity));

        //platform/provider/transactions
        $this->getJson('/api/v1/platform/provider/transactions', $headers)->assertSuccessful();

        //platform/employees?role=validation
        $this->getJson('/api/v1/platform/employees?role=' . Role::first()->key, $headers)->assertSuccessful();
        $this->getJson('/api/v1/platform/employees?role=invalid', $headers)->assertJsonValidationErrorFor('role');

        //platform/demo/transactions/{token}
        $response = $this->post('/api/v1/platform/demo/transactions', [], $headers);
        $response->assertSuccessful();

        $demo = DemoTransaction::find($response->json('data.id'));
        $this->getJson("/api/v1/platform/demo/transactions/$demo?->token", $headers)->assertSuccessful();
    }

    /**
     * @return void
     */
    public function testProviderVoucherEndpoints(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $voucher = $fund->makeVoucher($this->makeIdentity(), [], 100);
        $provider = $this->makeTestFundProvider($this->makeTestOrganization($this->makeIdentity()), $fund);
        $headers = $this->makeApiHeaders($provider->organization->identity);
        $address = $voucher->token_without_confirmation->address;

        //platform/provider/vouchers/{address}
        $this->getJson("/api/v1/platform/provider/vouchers/$address", $headers)->assertSuccessful();

        //platform/provider/vouchers/{address}/product-vouchers
        $this->getJson("/api/v1/platform/provider/vouchers/$address/product-vouchers", $headers)->assertSuccessful();

        //platform/provider/vouchers/{address}/products
        $this->getJson("/api/v1/platform/provider/vouchers/$address/products", $headers)->assertSuccessful();
    }

    /**
     * @return void
     */
    public function testRequesterVoucherEndpoints(): void
    {
        $fund = $this->makeTestFund($this->makeTestOrganization($this->makeIdentity()));
        $voucher = $fund->makeVoucher($this->makeIdentity($this->makeUniqueEmail()), [], 100);
        $headers = $this->makeApiHeaders($voucher->identity);
        $address = $voucher->token_without_confirmation->address;

        //platform/vouchers
        $response = $this->getJson('/api/v1/platform/vouchers', $headers);

        $response->assertSuccessful();
        $response->assertJsonCount(1, 'data');

        //platform/vouchers/{address}
        $response = $this->getJson("/api/v1/platform/vouchers/$address", $headers);

        $response->assertSuccessful();
        $response->assertJsonPath('data.number', $voucher->number);

        //platform/vouchers/{number}
        $response = $this->getJson("/api/v1/platform/vouchers/$voucher->number", $headers);

        $response->assertSuccessful();
        $response->assertJsonPath('data.number', $voucher->number);

        //platform/vouchers/{address}/send-email
        $this->postJson("/api/v1/platform/vouchers/$address/send-email", [], $headers)->assertSuccessful();
        $this->postJson("/api/v1/platform/vouchers/$voucher->number/send-email", [], $headers)->assertSuccessful();
    }

    public function testIdentityAuthEndpoints()
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $headers = $this->makeApiHeaders($identity);

        //identity
        $this->postJson('/api/v1/identity', [
            'email' => $this->makeUniqueEmail(),
        ])->assertSuccessful();

        //identity/proxy/email
        $this->postJson('/api/v1/identity/proxy/email', [
            'email' => $identity->email,
            'source' => Implementation::keysAvailable()->first(),
        ])->assertSuccessful();

        //identity/proxy/short-token
        $this->postJson('/api/v1/identity/proxy/short-token', [], $headers)->assertSuccessful();

        //identity/proxy/token
        $this->postJson('/api/v1/identity/proxy/token')->assertSuccessful();

        //identity/proxy/code
        $this->postJson('/api/v1/identity/proxy/code')->assertSuccessful();

        //identity/proxy/authorize/token
        $this->post('/api/v1/identity/proxy/authorize/token', [
            'auth_token' => Identity::makeProxy('qr_code')->exchange_token,
        ], $headers)->assertSuccessful();

        //identity/proxy/email/exchange/{emailToken}
        $token = $this->makeIdentityProxy($identity, false, 'email_code')->exchange_token;
        $this->getJson("/api/v1/identity/proxy/email/exchange/$token", $headers)->assertSuccessful();

        //identity/proxy/confirmation/exchange/
        $token = $this->makeIdentityProxy($identity, false)->exchange_token;
        $this->getJson("/api/v1/identity/proxy/confirmation/exchange/$token", $headers)->assertSuccessful();
    }
}
