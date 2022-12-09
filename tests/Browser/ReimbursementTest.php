<?php

namespace Tests\Browser;

use App\Mail\Reimbursements\ReimbursementSubmittedMail;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Reimbursement;
use App\Models\Voucher;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Laravel\Dusk\Browser;
use Facebook\WebDriver\Exception\TimeOutException;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestIdentities;

class ReimbursementTest extends DuskTestCase
{
    use AssertsSentEmails, MakesTestIdentities, WithFaker;

    /**
     * @return void
     * @throws \Throwable
     */
    public function testDraftReimbursementCreate(): void
    {
        Cache::clear();

        $this->makeReimbursementWithState(Implementation::byKey('nijmegen'), 'draft');
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testPendingReimbursementCreate(): void
    {
        Cache::clear();

        $this->makeReimbursementWithState(Implementation::byKey('nijmegen'), 'pending');
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testPendingReimbursementSkipEmailCreate(): void
    {
        Cache::clear();

        $this->makeReimbursementWithState(Implementation::byKey('nijmegen'), 'pending', false);
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testApprovedReimbursementCreate(): void
    {
        Cache::clear();

        $this->makeReimbursementWithState(Implementation::byKey('nijmegen'), 'approved');
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testDeclinedReimbursementCreate(): void
    {
        Cache::clear();

        $this->makeReimbursementWithState(Implementation::byKey('nijmegen'), 'declined');
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function testExpiredReimbursementCreate(): void
    {
        Cache::clear();

        $this->makeReimbursementWithState(Implementation::byKey('nijmegen'), 'expired');
    }

    /**
     * @param Implementation $implementation
     * @param string $state
     * @param bool $withEmail
     * @return void
     * @throws \Throwable
     */
    protected function makeReimbursementWithState(
        Implementation $implementation,
        string $state,
        bool $withEmail = true
    ): void {
        $this->assertNotNull($implementation);
        $this->assertNotNull($implementation->organization);

        // Got to identity and make user
        $identity = $this->makeIdentity($withEmail ? $this->makeUniqueEmail() : null);

        $this->browse(function (Browser $browser) use ($implementation, $identity, $state) {
            $browser->visit($implementation->urlWebshop());
            $this->loginIdentity($browser, $identity);

            $voucher = $implementation->funds[0]->makeVoucher($identity->address);
            $shouldSubmit = $state != 'draft';

            $data = $this->prepareReimbursementRequestForm($browser, $voucher);
            $this->saveReimbursement($browser);

            // assert reimbursement is created and all values are correct
            /** @var Reimbursement|null $reimbursement */
            $reimbursement = $voucher->reimbursements
                ->where('title', $data['title'])
                ->where('description', $data['description'])
                ->where('amount', $data['amount'])
                ->first();

            $this->assertNotNull($reimbursement);

            if ($shouldSubmit) {
                $this->submitDraftReimbursementRequest($browser, $reimbursement, $data);
            }

            $initialState = $shouldSubmit ? $reimbursement::STATE_PENDING : $reimbursement::STATE_DRAFT;
            $reimbursement->refresh();

            $this->assertTrue($reimbursement->state === $initialState);
            $this->assertReimbursementDataIsCorrect($reimbursement, $data);
            $this->assertReimbursementValuesAreCorrect($browser, $reimbursement, $data);

            $this->resolveReimbursement($reimbursement, $state);
            $reimbursement->refresh();
            $browser->refresh();

            $this->assertReimbursementValuesAreCorrect($browser, $reimbursement, $data);
            $this->assertReimbursementVoucherBalanceIsCorrect($reimbursement);

            // Logout
            $this->logout($browser);
        });
    }

    /**
     * @param Reimbursement $reimbursement
     * @param string $state
     * @return void
     * @throws \Throwable
     */
    protected function resolveReimbursement(Reimbursement $reimbursement, string $state): void
    {
        // assign the employee
        $reimbursement->assign($reimbursement->voucher->fund->employees[0]);

        if ($state === 'approved') {
            $reimbursement->approve();
            $reimbursement->refresh();

            $this->assertTrue($reimbursement->isApproved());
        }

        if ($state === 'declined') {
            $reimbursement->decline();
            $reimbursement->refresh();

            $this->assertTrue($reimbursement->isDeclined());
        }

        if ($state === 'expired') {
            $reimbursement->voucher->update(['expire_at' => now()->subDay()]);
            $reimbursement->refresh();

            $this->assertTrue($reimbursement->isExpired());
        }

        if ($state != 'expired') {
            $this->assertTrue($reimbursement->state === $state);
        }
    }

    /**
     * @param Browser $browser
     * @param Voucher $voucher
     * @return array
     * @throws \Throwable
     */
    protected function prepareReimbursementRequestForm(Browser $browser, Voucher $voucher): array
    {
        $browser->press('@userVouchers');
        $browser->waitFor('@menuBtnReimbursements');
        $browser->press('@menuBtnReimbursements');

        $browser->waitFor('@reimbursementsEmptyBlock');
        $browser->assertVisible('@reimbursementsEmptyBlock');
        $browser->waitFor('@btnEmptyBlock');
        $browser->press('@btnEmptyBlock');

        $browser->waitFor('@reimbursementEditContent');

        if (!$voucher->identity->email) {
            $browser->waitFor('@reimbursementNoEmail');
            $browser->assertMissing('@reimbursementForm');
            $browser->assertVisible('@reimbursementNoEmailAddBtn');
            $browser->assertVisible('@reimbursementNoEmailSkipBtn');
            $browser->press('@reimbursementNoEmailSkipBtn');
        }

        $browser->waitFor('@reimbursementForm');
        $browser->assertMissing('@reimbursementNoEmail');

        return $this->fillReimbursementForm($browser, $voucher);
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeOutException
     */
    protected function saveReimbursement(Browser $browser): void
    {
        $browser->waitFor('@reimbursementFormSave');
        $browser->pause(1000);
        $browser->press('@reimbursementFormSave');

        $browser->waitFor('@reimbursementsList');
    }

    /**
     * @param Browser $browser
     * @param array $data
     * @return void
     * @throws TimeOutException
     */
    protected function submitReimbursement(Browser $browser, array $data): void
    {
        $browser->waitFor('@reimbursementFormSubmit');
        $browser->press('@reimbursementFormSubmit');

        $browser->waitFor('@modalReimbursementConfirmation');

        $this->assertReimbursementOverview($browser, $data);

        $browser->waitFor('@modalReimbursementConfirmationSubmit');
        $browser->press('@modalReimbursementConfirmationSubmit');
        $browser->waitFor('@reimbursementsList', 10);
    }

    /**
     * @param Browser $browser
     * @param ?Reimbursement $reimbursement
     * @param array $data
     * @return void
     * @throws TimeOutException
     */
    protected function assertReimbursementValuesAreCorrect(
        Browser $browser,
        ?Reimbursement $reimbursement,
        array $data,
    ): void {
        $this->assertNotNull($reimbursement);
        $duskSelector = "@reimbursementsItem$reimbursement->id";

        $this->selectReimbursementTabByState($browser, $reimbursement);
        $browser->waitFor($duskSelector);
        $browser->press($duskSelector);
        $this->assertReimbursementDetailsPage($browser, $reimbursement, $data);

        $browser->back();
        $this->selectReimbursementTabByState($browser, $reimbursement);

        $browser->waitFor($duskSelector);
        $browser->within($duskSelector, function(Browser $browser) use ($reimbursement, $data) {
            $browser->assertSeeIn('@reimbursementsItemTitle', $data['title']);
            $browser->assertSeeIn('@reimbursementsItemFundName', $data['fund_name']);
            $browser->assertSeeIn('@reimbursementsItemAmount', currency_format_locale($data['amount']));
            $browser->assertSeeIn('@reimbursementsItemCode', "#$reimbursement->code");

            if (!$reimbursement->isExpired()) {
                $browser->assertVisible('@reimbursementsItemLabel' . ucfirst($reimbursement->state));
            }

            if ($reimbursement->isDraft()) {
                $browser->assertVisible('@reimbursementsItemBtnCancel');
            } else {
                $browser->assertMissing('@reimbursementsItemBtnCancel');
            }

            if ($reimbursement->isPending()) {
                $browser->waitFor('@reimbursementsItemDateSubmitted');
                $browser->assertSeeIn('@reimbursementsItemDateSubmitted', $reimbursement->submitted_at);
            }

            if ($reimbursement->isApproved()) {
                $browser->waitFor('@reimbursementsItemDateResolved');
                $browser->assertSeeIn('@reimbursementsItemDateResolved', $reimbursement->resolved_at_locale);
            }

            if ($reimbursement->isDeclined()) {
                $browser->waitFor('@reimbursementsItemDateDeclined');
                $browser->assertSeeIn('@reimbursementsItemDateDeclined', $reimbursement->resolved_at_locale);
            }

            if ($reimbursement->isExpired()) {
                $browser->waitFor('@reimbursementsItemDateExpired');
                $browser->assertSeeIn('@reimbursementsItemDateExpired', $reimbursement->expire_at_locale);
            }
        });
    }

    /**
     * @param Browser $browser
     * @param ?Reimbursement $reimbursement
     * @param array $data
     * @return void
     * @throws TimeOutException
     */
    protected function submitDraftReimbursementRequest(
        Browser $browser,
        ?Reimbursement $reimbursement,
        array $data,
    ): void {
        $this->assertNotNull($reimbursement);
        $duskSelector = "@reimbursementsItem$reimbursement->id";

        $submitTime = now();
        $requesterEmail = $reimbursement->voucher->identity->email;

        $this->selectReimbursementTabByState($browser, $reimbursement);
        $browser->waitFor($duskSelector);
        $browser->press($duskSelector);

        $browser->waitFor('@reimbursementDetailsPage');
        $browser->assertVisible('@reimbursementOverviewEditButton');
        $browser->assertVisible('@reimbursementDetailsPageDeleteBtn');
        $browser->press('@reimbursementOverviewEditButton');

        $browser->waitFor('@reimbursementEditContent');

        $this->submitReimbursement($browser, $data);

        if ($requesterEmail) {
            $this->assertMailableSent($requesterEmail, ReimbursementSubmittedMail::class, $submitTime);
        }
    }

    /**
     * @param Reimbursement|null $reimbursement
     * @param array $data
     * @return void
     */
    protected function assertReimbursementDataIsCorrect(
        ?Reimbursement $reimbursement,
        array $data
    ): void{
        $this->assertTrue($reimbursement->title == $data['title']);
        $this->assertTrue($reimbursement->description == $data['description']);
        $this->assertTrue($reimbursement->voucher->fund->name == $data['fund_name']);
        $this->assertTrue($reimbursement->voucher->fund->organization->name == $data['sponsor_name']);
        $this->assertTrue($reimbursement->iban == $data['iban']);
        $this->assertTrue($reimbursement->iban_name == $data['iban_name']);
    }

    /**
     * @param Reimbursement $reimbursement
     * @return void
     */
    protected function assertReimbursementVoucherBalanceIsCorrect(Reimbursement $reimbursement): void
    {
        if ($reimbursement->isDeclined() || $reimbursement->isDraft()) {
            $this->assertTrue($reimbursement->voucher->amount_spent == 0);
        } else {
            $this->assertTrue($reimbursement->voucher->amount_spent == $reimbursement->amount);
        }
    }

    /**
     * @param Browser $browser
     * @param array $data
     * @return void
     * @throws TimeOutException
     */
    protected function assertReimbursementOverview(Browser $browser, array $data): void
    {
        $browser->waitFor('@reimbursementOverview');
        $browser->within('@reimbursementOverview', function(Browser $browser) use ($data) {
            $browser->assertSeeIn('@reimbursementOverviewTitle', $data['title']);
            $browser->assertSeeIn('@reimbursementOverviewAmount', currency_format_locale($data['amount']));
            $browser->assertSeeIn('@reimbursementOverviewSponsorName', $data['sponsor_name']);
            $browser->assertSeeIn('@reimbursementOverviewFundName', $data['fund_name']);
            $browser->assertSeeIn('@reimbursementOverviewIban', $data['iban']);
            $browser->assertSeeIn('@reimbursementOverviewIbanName', $data['iban_name']);

            if ($browser->element('@reimbursementOverviewDescription')) {
                $browser->assertSeeIn('@reimbursementOverviewDescription', $data['description']);
            }
        });
    }

    /**
     * @param Browser $browser
     * @param Reimbursement $reimbursement
     * @param array $data
     * @return void
     * @throws TimeOutException
     */
    protected function assertReimbursementDetailsPage(
        Browser $browser,
        Reimbursement $reimbursement,
        array $data
    ): void {
        $browser->waitFor('@reimbursementDetailsPage');
        $browser->within('@reimbursementDetailsPage', function(Browser $browser) use ($data, $reimbursement) {
            $this->assertReimbursementOverview($browser, $data);
        });

        if ($reimbursement->isDraft()) {
            $browser->assertVisible('@reimbursementOverviewEditButton');
            $browser->assertVisible('@reimbursementDetailsPageDeleteBtn');
            $browser->press('@reimbursementOverviewEditButton');
            $browser->waitFor('@reimbursementEditContent');
            $browser->back();
            $browser->waitFor('@reimbursementDetailsPage');
        } else {
            $browser->assertMissing('@reimbursementOverviewEditButton');
            $browser->assertMissing('@reimbursementDetailsPageDeleteBtn');
        }
    }
    /**
     * @throws \Throwable
     */
    protected function fillReimbursementForm(Browser $browser, Voucher $voucher): array
    {
        $formData = $this->makeReimbursementData($voucher);

        $browser->within('@reimbursementForm', function(Browser $browser) use ($voucher, $formData) {
            $browser->type('title', $formData['title']);
            $browser->type('amount', $formData['amount']);
            $browser->type('description', $formData['description']);
            $browser->type('iban', $formData['iban']);
            $browser->type('iban_name', $formData['iban_name']);

            $browser->press('@voucherSelector');
            $browser->waitFor('@voucherSelectorOptions');
            $browser->press("@voucherSelectorOption$voucher->id");

            $browser->within('@fileUploader', function (Browser $browser) {
                $browser->script("document.querySelector('.droparea-hidden-input').style.display = 'block'");
                $browser->waitFor('[name=file_uploader_input_hidden]');
                $browser->assertVisible('[name=file_uploader_input_hidden]');
                $browser->element('[name=file_uploader_input_hidden]');
                $browser->attach('file_uploader_input_hidden', base_path('tests/assets/test.png'));
                $browser->script("document.querySelector('.droparea-hidden-input').style.display = 'none'");
            });
        });

        $browser->waitFor('@modalPhotoCropper');
        $browser->waitFor('@modalPhotoCropperSubmit');
        $browser->waitUntilEnabled('@modalPhotoCropperSubmit');
        $browser->press('@modalPhotoCropperSubmit');

        return $formData;
    }

    /**
     * @return string[]
     * @throws \Throwable
     */
    protected function makeReimbursementData(Voucher $voucher): array
    {
        return [
            'title' => $this->faker->text(60),
            'description' => $this->faker->text(600),
            'amount' => random_int(1, 10),
            'iban' => $this->faker()->iban('NL'),
            'iban_name' => $this->faker()->firstName . ' ' . $this->faker()->lastName,
            'fund_name' => $voucher->fund->name,
            'sponsor_name' => $voucher->fund->organization->name,
            'voucher_id' => $voucher->id,
        ];
    }

    /**
     * @throws TimeOutException
     */
    protected function loginIdentity(Browser $browser, Identity $identity)
    {
        $proxy = $this->makeIdentityProxy($identity);
        $browser->script("localStorage.setItem('active_account', '$proxy->access_token')");
        $browser->refresh();

        if ($identity->email) {
            $browser->waitFor('@identityEmail');
            $browser->assertSeeIn('@identityEmail', $identity->email);
        } else {
            $browser->waitFor('@userVouchers');
        }
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeOutException
     */
    private function logout(Browser $browser): void
    {
        $browser->refresh();

        $browser->waitFor('@userProfile');
        $browser->element('@userProfile')->click();

        $browser->waitFor('@btnUserLogout');
        $browser->element('@btnUserLogout')->click();
    }

    /**
     * @param Browser $browser
     * @param Reimbursement $reimbursement
     * @return void
     * @throws TimeOutException
     */
    private function selectReimbursementTabByState(
        Browser $browser,
        Reimbursement $reimbursement,
    ): void {
        if ($reimbursement->expired) {
            $browser->waitFor('@reimbursementsFilterExpired');
            $browser->press('@reimbursementsFilterExpired');
        }

        $browser->waitFor('@reimbursementsList', 10);
    }
}
