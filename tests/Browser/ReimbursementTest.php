<?php

namespace Tests\Browser;

use App\Mail\Reimbursements\ReimbursementSubmittedMail;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Reimbursement;
use App\Models\Voucher;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Laravel\Dusk\Browser;
use Facebook\WebDriver\Exception\TimeOutException;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestIdentities;

class ReimbursementTest extends DuskTestCase
{
    use AssertsSentEmails, MakesTestIdentities, WithFaker, HasFrontendActions;

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
            $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

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
    protected function resolveReimbursement(
        Reimbursement $reimbursement,
        string $state,
    ): void {
        if ($state == 'pending' || $reimbursement->isDraft()) {
            return;
        }

        $this->browse(function (Browser $browser) use ($reimbursement, $state) {
            $this->goToDashboardReimbursementsPage($browser, $reimbursement->voucher->fund->organization);
            $this->searchDashboardReimbursement($browser, $reimbursement);
            $this->goToDashboardReimbursementDetailsPage($browser, $reimbursement);

            $this->assignReimbursement($browser);

            $reimbursement->refresh();
            $this->assertNotNull($reimbursement->employee);

            if ($state === 'approved') {
                $browser->waitFor('@reimbursementApprove');
                $browser->element('@reimbursementApprove')->click();
                $browser->waitFor('@reimbursementResolveSubmit');
                $browser->element('@reimbursementResolveSubmit')->click();
                $browser->waitFor('@reimbursementsPage', 8);
                $browser->pause(3000);
                $reimbursement->refresh();

                $this->assertTrue($reimbursement->isApproved());
            }

            if ($state === 'declined') {
                $browser->waitFor('@reimbursementDecline');
                $browser->element('@reimbursementDecline')->click();
                $browser->waitFor('@reimbursementResolveSubmit');
                $browser->element('@reimbursementResolveSubmit')->click();
                $browser->waitFor('@reimbursementsPage', 8);
                $browser->pause(3000);
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
        });
    }

    /**
     * @param Browser $browser
     * @return void
     * @throws TimeOutException
     */
    private function assignReimbursement(Browser $browser) : void
    {
        $browser->waitFor('@reimbursementAssign');
        $browser->element('@reimbursementAssign')->click();
        $browser->waitFor('@successNotification');
    }

    /**
     * @param Browser $browser
     * @param Voucher $voucher
     * @return array
     * @throws \Throwable
     */
    protected function prepareReimbursementRequestForm(Browser $browser, Voucher $voucher): array
    {
        $this->goToReimbursementsPage($browser);

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
    private function goToReimbursementsPage(Browser $browser) : void {
        $browser->press('@userVouchers');
        $browser->pause(1000);
        $browser->waitFor('@menuBtnReimbursements');
        $browser->press('@menuBtnReimbursements');
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
     * @param Reimbursement|null $reimbursement
     * @param array $data
     * @return void
     * @throws TimeOutException
     * @throws \Throwable
     */
    protected function assertReimbursementValuesAreCorrect(
        Browser $browser,
        ?Reimbursement $reimbursement,
        array $data,
    ): void {
        $this->assertNotNull($reimbursement);
        $this->assertWebshopReimbursementValuesAreCorrect($browser, $reimbursement, $data);

        if (!$reimbursement->isDraft()) {
            $this->assertDashboardReimbursementValuesAreCorrect($browser, $reimbursement, $data);
        }
    }

    /**
     * @param Browser $browser
     * @param Reimbursement|null $reimbursement
     * @param array $data
     * @return void
     * @throws TimeOutException
     */
    protected function assertWebshopReimbursementValuesAreCorrect(
        Browser $browser,
        ?Reimbursement $reimbursement,
        array $data,
    ): void {
        $browser->visit($reimbursement->voucher->fund->fund_config->implementation->urlWebshop());
        $this->loginIdentity($browser, $reimbursement->voucher->identity);
        $this->assertIdentityAuthenticatedOnWebshop($browser, $reimbursement->voucher->identity);
        $this->goToReimbursementsPage($browser);

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
     * @param Organization|null $organization
     * @return void
     * @throws TimeOutException
     */
    protected function goToDashboardReimbursementsPage(
        Browser $browser,
        ?Organization $organization,
    ): void {
        $this->assertNotNull($organization);

        $browser->visit(Implementation::general()->urlSponsorDashboard());

        // Authorize identity
        $this->loginIdentity($browser, $organization->identity);
        $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $organization->identity);
        $this->selectDashboardOrganization($browser, $organization);

        $browser->waitFor('@reimbursementsPage');
        $browser->element('@reimbursementsPage')->click();
    }

    /**
     * @param Browser $browser
     * @param Reimbursement $reimbursement
     * @return void
     * @throws TimeOutException
     */
    private function searchDashboardReimbursement(
        Browser $browser,
        Reimbursement $reimbursement
    ): void {
        $fundSelector = "@fundSelectorOption" . $reimbursement->voucher->fund_id;

        $browser->waitFor($fundSelector);
        $browser->element($fundSelector)->click();

        $browser->refresh();
        $this->selectReimbursementTabByState($browser, $reimbursement);

        if ($reimbursement->voucher?->identity?->email) {
            $browser->waitFor('@searchReimbursement');
            $browser->type('@searchReimbursement', $reimbursement->voucher->identity->email);
            $browser->waitFor("@reimbursement$reimbursement->id", 5);
            $browser->assertVisible("@reimbursement$reimbursement->id");
        }
    }

    /**
     * @param Browser $browser
     * @param Reimbursement|null $reimbursement
     * @param array $data
     * @return void
     * @throws \Throwable
     */
    protected function assertDashboardReimbursementValuesAreCorrect(
        Browser $browser,
        ?Reimbursement $reimbursement,
        array $data,
    ): void {
        // $this->browse(function (Browser $browser) use ($reimbursement, $data) {
            // Go to reimbursements page and check if the previously added reimbursement is in the list
            $this->goToDashboardReimbursementsPage($browser, $reimbursement->voucher->fund->organization);
            $this->searchDashboardReimbursement($browser, $reimbursement);
            $this->assertDashboardReimbursementPage($browser, $reimbursement, $data);

            // Go to reimbursements details page and check the data
            $this->goToDashboardReimbursementDetailsPage($browser, $reimbursement);
            $this->assertDashboardReimbursementDetails($browser, $reimbursement, $data);
        //});
    }

    /**
     * @param Browser $browser
     * @param Reimbursement $reimbursement
     * @param array $data
     * @return void
     * @throws TimeOutException
     */
    private function assertDashboardReimbursementPage(
        Browser $browser,
        Reimbursement $reimbursement,
        array $data
    ) : void {
        $browser->waitFor("@reimbursementIdentityEmail$reimbursement->id", 10);
        $browser->assertSeeIn(
            '@reimbursementIdentityEmail' . $reimbursement->id,
            $reimbursement->voucher->identity->email ?: 'Geen E-mail'
        );
        $browser->assertSeeIn('@reimbursementAmount'.$reimbursement->id, currency_format_locale($data['amount']));
        $browser->assertSeeIn('@reimbursementState'.$reimbursement->id, $reimbursement->state_locale);
    }

    /**
     * @param Browser $browser
     * @param Reimbursement $reimbursement
     * @return void
     * @throws TimeOutException
     */
    private function goToDashboardReimbursementDetailsPage(
        Browser $browser,
        Reimbursement $reimbursement,
    ): void {
        $browser->element("@reimbursement". $reimbursement->id)->click();
        $browser->waitFor('@reimbursementDetails');
    }

    /**
     * @param Browser $browser
     * @param Reimbursement $reimbursement
     * @param array $data
     * @return void
     */
    private function assertDashboardReimbursementDetails(
        Browser $browser,
        Reimbursement $reimbursement,
        array $data
    ) : void {
        $browser->assertSeeIn('@reimbursementIBAN', $data['iban']);
        $browser->assertSeeIn('@reimbursementIBANName', $data['iban_name']);
        $browser->assertSeeIn('@reimbursementAmount', currency_format_locale($data['amount']));
        $browser->assertSeeIn('@reimbursementState', $reimbursement->state_locale);
        $browser->assertSeeIn('@reimbursementTitle', $data['title']);
        $browser->assertSeeIn('@reimbursementDescription', $data['description']);
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
        $browser->waitFor('@modalPhotoCropperSubmit', 10);
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
            $browser->waitFor('@reimbursementsFilterArchived', 10);
            $browser->press('@reimbursementsFilterArchived');
        }

        $browser->waitFor('@reimbursementsList', 10);
    }
}
