<?php

namespace Tests\Feature;

use App\Events\Funds\FundBalanceLowEvent;
use App\Events\Vouchers\VoucherSendToEmailBySponsorEvent;
use App\Mail\Funds\FundBalanceWarningMail;
use App\Mail\Vouchers\SendVoucherBySponsorMail;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request;
use Illuminate\Mail\MailManager;
use Illuminate\Mail\SendQueuedMailable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\FakesMicrosoftGraph;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;

class MicrosoftGraphTransportTest extends TestCase
{
    use DoesTesting;
    use FakesMicrosoftGraph;
    use MakesTestFunds;
    use CreatesApplication;
    use MakesTestIdentities;
    use DatabaseTransactions;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testMailUsesDefaultMailerWithoutCustomConfiguration(): void
    {
        Http::fake();

        $startDate = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = $this->makeTestImplementation($organization);
        $fund = $this->makeTestFund($organization, implementation: $implementation);

        $voucher = $fund->makeVoucher();
        $email = $this->makeIdentity($this->makeUniqueEmail())->email;

        $this->assertContains(SendVoucherBySponsorMail::class, Config::get('forus.mail.custom_mailer_allowlist'));
        $this->assertFalse($implementation->hasCustomMailer());

        Event::dispatch(new VoucherSendToEmailBySponsorEvent($voucher, $email));
        $this->assertMailableSent($email, SendVoucherBySponsorMail::class, $startDate);

        Http::assertNotSent(function ($request) {
            return $request->url() === 'https://graph.microsoft.com/v1.0/users/sender@example.com/sendMail';
        });
    }

    /**
     * @return void
     */
    public function testQueuedMailUsesImplementationSpecificGraphConfiguration(): void
    {
        $this->fakeMicrosoftGraph([
            'tenant-id' => 'test-access-token',
            'tenant-id-2' => 'test-access-token-2',
        ]);

        $startDate = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = $this->makeTestImplementation($organization);
        $fund = $this->makeTestFund($organization, implementation: $implementation);

        $voucher = $fund->makeVoucher();
        $email = $this->makeIdentity($this->makeUniqueEmail())->email;

        $this->assertContains(SendVoucherBySponsorMail::class, Config::get('forus.mail.custom_mailer_allowlist'));

        $implementation->forceFill([
            'custom_mailer_config' => $this->makeCustomMailerConfig(),
        ])->save();

        $this->assertTrue($implementation->hasCustomMailer());

        $secondImplementation = $this->makeTestImplementation($organization);
        $secondImplementation->forceFill([
            'custom_mailer_config' => $this->makeCustomMailerConfig([
                'tenant_id' => 'tenant-id-2',
                'client_id' => 'client-id-2',
                'client_secret' => 'client-secret-2',
                'from_email' => 'sender-2@example.com',
            ]),
        ])->save();

        $secondFund = $this->makeTestFund($organization, implementation: $secondImplementation);

        Queue::fake([SendQueuedMailable::class]);
        Mail::forgetMailers();
        $this->app->forgetInstance('forus.services.notification');

        Event::dispatch(new VoucherSendToEmailBySponsorEvent($voucher, $email));
        Event::dispatch(new VoucherSendToEmailBySponsorEvent($secondFund->makeVoucher(), $email));

        Queue::assertPushed(SendQueuedMailable::class, 2);

        $this->app->forgetScopedInstances();
        Mail::forgetMailers();

        foreach (Queue::pushed(SendQueuedMailable::class) as $queuedMail) {
            /** @var SendQueuedMailable $queuedMail */
            $queuedMail = unserialize(serialize($queuedMail));
            $queuedMail->handle(resolve(MailManager::class));
        }

        $this->assertMailableSent($email, SendVoucherBySponsorMail::class, $startDate);
        $this->assertGraphMailSent('sender@example.com', 'test-access-token');
        $this->assertGraphMailSent('sender-2@example.com', 'test-access-token-2');
    }

    /**
     * @return void
     */
    public function testQueuedMailUsesCurrentImplementationGraphConfiguration(): void
    {
        $this->fakeMicrosoftGraph([
            'tenant-id' => 'test-access-token',
            'tenant-id-2' => 'test-access-token-2',
        ]);

        $startDate = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = $this->makeTestImplementation($organization);
        $fund = $this->makeTestFund($organization, implementation: $implementation);
        $email = $this->makeIdentity($this->makeUniqueEmail())->email;

        $implementation->forceFill([
            'custom_mailer_config' => $this->makeCustomMailerConfig(),
        ])->save();

        Queue::fake([SendQueuedMailable::class]);
        Mail::forgetMailers();
        $this->app->forgetInstance('forus.services.notification');

        Event::dispatch(new VoucherSendToEmailBySponsorEvent($fund->makeVoucher(), $email));
        Queue::assertPushed(SendQueuedMailable::class, 1);

        $serializedMail = serialize(Queue::pushed(SendQueuedMailable::class)->first());

        $implementation->forceFill([
            'custom_mailer_config' => $this->makeCustomMailerConfig([
                'tenant_id' => 'tenant-id-2',
                'client_id' => 'client-id-2',
                'client_secret' => 'client-secret-2',
                'from_email' => 'sender-2@example.com',
            ]),
        ])->save();

        $this->app->forgetScopedInstances();
        Mail::forgetMailers();

        /** @var SendQueuedMailable $queuedMail */
        $queuedMail = unserialize($serializedMail);
        $queuedMail->handle(resolve(MailManager::class));

        $this->assertMailableSent($email, SendVoucherBySponsorMail::class, $startDate);
        $this->assertGraphMailSent('sender-2@example.com', 'test-access-token-2');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://login.microsoftonline.com/tenant-id-2/oauth2/v2.0/token'
                && $request['client_id'] === 'client-id-2'
                && $request['client_secret'] === 'client-secret-2';
        });
    }

    /**
     * @return void
     */
    public function testMailNotUseMicrosoftGraphWhenNotUsingCustomMailer(): void
    {
        $this->fakeMicrosoftGraph([
            'tenant-id' => 'test-access-token',
        ]);

        $startDate = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $implementation = $this->makeTestImplementation($organization);
        $fund = $this->makeTestFund($organization, implementation: $implementation);

        $implementation->forceFill([
            'custom_mailer_config' => $this->makeCustomMailerConfig(),
        ])->save();

        $this->assertTrue($implementation->hasCustomMailer());
        $this->assertNotContains(FundBalanceWarningMail::class, Config::get('forus.mail.custom_mailer_allowlist'));

        Event::dispatch(new FundBalanceLowEvent($fund));
        $this->assertMailableSent($organization->identity->email, FundBalanceWarningMail::class, $startDate);

        Http::assertNotSent(function ($request) {
            return $request->url() === 'https://graph.microsoft.com/v1.0/users/sender@example.com/sendMail';
        });
    }

    /**
     * @param array $overrides
     * @return array
     */
    private function makeCustomMailerConfig(array $overrides = []): array
    {
        return array_merge([
            'transport' => 'microsoft-graph',
            'tenant_id' => 'tenant-id',
            'client_id' => 'client-id',
            'client_secret' => 'client-secret',
            'from_email' => 'sender@example.com',
        ], $overrides);
    }

    /**
     * @param string $fromEmail
     * @param string $accessToken
     * @return void
     */
    private function assertGraphMailSent(string $fromEmail, string $accessToken): void
    {
        Http::assertSent(function (Request $request) use ($fromEmail, $accessToken): bool {
            return $request->url() === "https://graph.microsoft.com/v1.0/users/$fromEmail/sendMail"
                && $request->method() === 'POST'
                && $request->header('Authorization')[0] === "Bearer $accessToken";
        });
    }
}
