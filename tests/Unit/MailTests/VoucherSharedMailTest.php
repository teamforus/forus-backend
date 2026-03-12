<?php

namespace Tests\Unit\MailTests;

use App\Events\Vouchers\VoucherSendToEmailBySponsorEvent;
use App\Mail\Vouchers\SendProductVoucherBySponsorMail;
use App\Mail\Vouchers\SendVoucherBySponsorMail;
use App\Services\MailDatabaseLoggerService\Models\EmailLog;
use App\Traits\DoesTesting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;

class VoucherSharedMailTest extends TestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use CreatesApplication;
    use MakesTestIdentities;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesTestVouchers;

    /**
     * Test voucher share email send.
     * We can send voucher by sponsor to custom email only if there is identity with such email
     * and voucher not granted.
     *
     * @return void
     */
    public function testVoucherShareMail(): void
    {
        $startDate = now();
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher = $fund->makeVoucher();

        // trigger event to send email by sponsor with empty email
        Event::dispatch(new VoucherSendToEmailBySponsorEvent($voucher, null));

        // assert no email was sent
        $emailQuery = EmailLog::where(function (Builder $builder) use ($startDate) {
            $builder->where('created_at', '>=', $startDate);
            $builder->where('mailable', SendVoucherBySponsorMail::class);
        });

        $this->assertFalse($emailQuery->exists());

        // assert no email sent if we pass email but there are no identity with such email
        $email = $this->makeUniqueEmail();
        Event::dispatch(new VoucherSendToEmailBySponsorEvent($voucher, $email));
        $this->assertMailableNotSent($email, SendVoucherBySponsorMail::class, $startDate);

        // assert email was sent to email as identity exists
        $email = $this->makeIdentity($this->makeUniqueEmail())->email;
        Event::dispatch(new VoucherSendToEmailBySponsorEvent($voucher, $email));
        $this->assertMailableSent($email, SendVoucherBySponsorMail::class, $startDate);

        // wait 1 sec to assert that passed email ignored if voucher is granted
        sleep(1);
        $startDate = now();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $voucher->assignToIdentity($identity);

        Event::dispatch(new VoucherSendToEmailBySponsorEvent($voucher, $email));

        $this->assertMailableNotSent($email, SendVoucherBySponsorMail::class, $startDate);
        $this->assertMailableSent($identity->email, SendVoucherBySponsorMail::class, $startDate);
    }

    /**
     * @return void
     */
    public function testSponsorVoucherShareMailReplacesAmountAndExpirationDate(): void
    {
        $startDate = now();
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'show_qr_code' => true,
        ]);

        $voucher = $fund->makeVoucher();
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        Event::dispatch(new VoucherSendToEmailBySponsorEvent($voucher, $identity->email));

        $emailLog = $this->findEmailLog($identity, SendVoucherBySponsorMail::class, $startDate);

        $this->assertStringNotContainsString(':voucher_amount_locale', $emailLog->content);
        $this->assertStringNotContainsString(':expiration_date', $emailLog->content);
    }

    /**
     * @return void
     */
    public function testSponsorProductVoucherShareMailIncludesProductAndProvider(): void
    {
        $startDate = now();
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'show_qr_code' => true,
        ]);

        $product = $this->makeTestProviderWithProducts(1)[0];
        $voucher = $this->makeTestProductVoucher($fund, productId: $product->id);
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        Event::dispatch(new VoucherSendToEmailBySponsorEvent($voucher, $identity->email));

        $emailLog = $this->findEmailLog($identity, SendProductVoucherBySponsorMail::class, $startDate);

        $this->assertStringContainsString($product->name, $emailLog->content);
        $this->assertStringContainsString($product->organization->name, $emailLog->content);
    }
}
