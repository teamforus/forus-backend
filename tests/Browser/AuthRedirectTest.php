<?php

namespace Tests\Browser;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\ProductReservation;
use App\Services\DigIdService\Models\DigIdSession;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\TestsReservations;
use Throwable;

class AuthRedirectTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use TestsReservations;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestFundRequests;

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthRedirectWithOneFundWithoutVoucher()
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->makeTestFund($implementation->organization, [], [
            'outcome_type' => 'voucher',
            'bsn_confirmation_time' => 900,
            'bsn_confirmation_api_time' => 900,
            'allow_fund_requests' => true,
            'allow_prevalidations' => true,
        ]);

        $this->rollbackModels([
            [$implementation, $implementation->only(['digid_enabled', 'digid_required'])],
        ], function () use ($implementation, $organization, $fund) {
            $implementation->forceFill([
                'digid_enabled' => true,
                'digid_required' => true,
                'digid_connection_type' => DigIdSession::CONNECTION_TYPE_CGI,
                'digid_app_id' => 'test',
                'digid_shared_secret' => 'test',
                'digid_a_select_server' => 'test',
            ])->save();

            $organization
                ->funds
                ->filter(fn (Fund $item) => $item->id !== $fund->id)
                ->each(fn (Fund $fund) => $fund->update(['state' => Fund::STATE_CLOSED]));

            $requester = $this->makeIdentity($this->makeUniqueEmail());

            $this->browse(function (Browser $browser) use ($implementation, $requester) {
                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $browser->visit($this->getAuthLink($implementation, $requester->primary_email->email));

                // assert requester was redirected to fund activate because only one fund available
                $browser->waitFor('@fundRequestOptions');

                $this->logout($browser);
            });
        }, function () use ($fund, $organization) {
            $fund && $this->deleteFund($fund);
            $organization->funds->each(function (Fund $fund) {
                $fund->update(['state' => Fund::STATE_ACTIVE]);
            });
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthRedirectWithSeveralFundsWithoutVouchers()
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $funds = collect([
            $this->makeTestFund($implementation->organization),
            $this->makeTestFund($implementation->organization),
        ]);

        $this->rollbackModels([], function () use ($implementation, $organization, $funds) {
            $organization
                ->funds
                ->filter(fn (Fund $item) => !in_array($item->id, $funds->pluck('id')->all()))
                ->each(fn (Fund $fund) => $fund->update(['state' => Fund::STATE_CLOSED]));

            $requester = $this->makeIdentity($this->makeUniqueEmail());

            $this->browse(function (Browser $browser) use ($implementation, $requester) {
                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $browser->visit($this->getAuthLink($implementation, $requester->primary_email->email));

                // assert requester was redirected to funds page when several funds exist
                // where requester does not have voucher
                $browser->waitFor('@fundsList');

                $this->logout($browser);
            });
        }, function () use ($funds, $organization) {
            $funds->each(fn (Fund $fund) => $this->deleteFund($fund));
            $organization->funds->each(function (Fund $fund) {
                $fund->update(['state' => Fund::STATE_ACTIVE]);
            });
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthRedirectWithOneFundAndVoucher()
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;
        $fund = $this->makeTestFund($implementation->organization);

        $this->rollbackModels([], function () use ($implementation, $organization, $fund) {
            $organization
                ->funds
                ->filter(fn (Fund $item) => $item->id !== $fund->id)
                ->each(fn (Fund $fund) => $fund->update(['state' => Fund::STATE_CLOSED]));

            $requester = $this->makeIdentity($this->makeUniqueEmail());
            $fund->makeVoucher($requester);

            $this->browse(function (Browser $browser) use ($implementation, $requester, $fund) {
                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $browser->visit($this->getAuthLink($implementation, $requester->primary_email->email));

                // assert requester was redirected to voucher page when requester has only one voucher
                $browser->waitFor('@voucherTitle')->assertSeeIn('@voucherTitle', $fund->name);

                $this->logout($browser);
            });
        }, function () use ($fund, $organization) {
            $fund && $this->deleteFund($fund);
            $organization->funds->each(function (Fund $fund) {
                $fund->update(['state' => Fund::STATE_ACTIVE]);
            });
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthRedirectWithSeveralFundsWithVouchers()
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $funds = collect([
            $this->makeTestFund($implementation->organization),
            $this->makeTestFund($implementation->organization),
        ]);

        $this->rollbackModels([], function () use ($implementation, $organization, $funds) {
            $organization
                ->funds
                ->filter(fn (Fund $item) => !in_array($item->id, $funds->pluck('id')->all()))
                ->each(fn (Fund $fund) => $fund->update(['state' => Fund::STATE_CLOSED]));

            $requester = $this->makeIdentity($this->makeUniqueEmail());
            $funds->each(fn (Fund $fund) => $fund->makeVoucher($requester));

            $this->browse(function (Browser $browser) use ($implementation, $requester) {
                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $browser->visit($this->getAuthLink($implementation, $requester->primary_email->email));

                // assert requester was redirected to vouchers page when several funds exist
                // where requester have vouchers
                $browser->waitFor('@vouchersList');

                $this->logout($browser);
            });
        }, function () use ($funds, $organization) {
            $funds->each(fn (Fund $fund) => $this->deleteFund($fund));
            $organization->funds->each(function (Fund $fund) {
                $fund->update(['state' => Fund::STATE_ACTIVE]);
            });
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthRedirectToHomeWithoutFunds()
    {
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $this->rollbackModels([], function () use ($implementation, $organization) {
            $organization
                ->funds
                ->each(fn (Fund $fund) => $fund->update(['state' => Fund::STATE_CLOSED]));

            $requester = $this->makeIdentity($this->makeUniqueEmail());

            $this->browse(function (Browser $browser) use ($implementation, $requester) {
                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $browser->visit($this->getAuthLink($implementation, $requester->primary_email->email));

                // assert requester was redirected to home page when no funds
                $browser->waitFor('@header');
                $browser->assertSeeIn('@headerTitle', $implementation->name);

                $this->logout($browser);
            });
        }, function () use ($organization) {
            $organization->funds->each(function (Fund $fund) {
                $fund->update(['state' => Fund::STATE_ACTIVE]);
            });
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthRedirectWithTargetFundRequest()
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this
            ->makeTestFund($implementation->organization)
            ->syncCriteria([[
                'record_type_key' => 'children_nth',
                'operator' => '>',
                'value' => 2,
                'show_attachment' => false,
            ]])
            ->refresh();

        $this->rollbackModels([], function () use ($implementation, $fund) {
            $requester = $this->makeIdentity($this->makeUniqueEmail());

            $fundRequest = $this->setCriteriaAndMakeFundRequest($requester, $fund, [
                'children_nth' => 3,
            ]);

            $this->approveFundRequest($fundRequest);

            $this->browse(function (Browser $browser) use ($implementation, $requester, $fund, $fundRequest) {
                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $browser->visit($this->getAuthLink(
                    $implementation,
                    $requester->primary_email->email,
                    "fundRequest-$fundRequest->id"
                ));

                // assert requester was redirected to fund request page if target "fundRequest"
                $browser->waitFor('@fundRequestFund')->assertSeeIn('@fundRequestFund', $fund->name);

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthRedirectWithTargetVoucher()
    {
        $implementation = Implementation::byKey('nijmegen');
        $fund = $this->makeTestFund($implementation->organization);

        $this->rollbackModels([], function () use ($implementation, $fund) {
            $requester = $this->makeIdentity($this->makeUniqueEmail());
            $voucher = $fund->makeVoucher($requester);

            $this->browse(function (Browser $browser) use ($implementation, $requester, $fund, $voucher) {
                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $browser->visit($this->getAuthLink(
                    $implementation,
                    $requester->primary_email->email,
                    "voucher-$voucher->number"
                ));

                // assert requester was redirected to voucher page when requester has only one voucher
                $browser->waitFor('@voucherTitle')->assertSeeIn('@voucherTitle', $fund->name);

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthRedirectWithTargetProductReservation()
    {
        $implementation = Implementation::byKey('nijmegen');
        $fund = $this->makeTestFund($implementation->organization, fundConfigsData: [
            'allow_reservations' => true,
        ]);

        $this->rollbackModels([], function () use ($implementation, $fund) {
            $requester = $this->makeIdentity($this->makeUniqueEmail());
            $reservation = $this->makeReservation($fund, $requester);

            $this->browse(function (Browser $browser) use ($implementation, $requester, $reservation) {
                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $browser->visit($this->getAuthLink(
                    $implementation,
                    $requester->primary_email->email,
                    "productReservation-$reservation->product_id"
                ));

                // assert requester was redirected to product page
                $browser->waitFor('@productName')->assertSeeIn('@productName', $reservation->product->name);

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Fund $fund
     * @param Identity $identity
     * @return ProductReservation
     */
    protected function makeReservation(Fund $fund, Identity $identity): ProductReservation
    {
        $provider = $this->makeTestProviderOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $fundProvider = $this->makeTestFundProvider($provider, $fund);
        $this->assertNotNull($fundProvider);

        $voucher = $fund->makeVoucher($identity);
        $product = $this->makeTestProductForReservation($fundProvider->organization);

        $response = $this->makeReservationStoreRequest($voucher, $product);
        $response->assertSuccessful();

        return ProductReservation::find($response->json('data.id'));
    }

    /**
     * @param Implementation $implementation
     * @param string $email
     * @param string $target
     * @return string
     */
    protected function getAuthLink(Implementation $implementation, string $email, string $target = ''): string
    {
        $proxy = Identity::findByEmail($email)->makeAuthorizationEmailProxy();

        return url(sprintf(
            '/api/v1/identity/proxy/email/redirect/%s?%s',
            $proxy->exchange_token,
            http_build_query([
                'target' => $target,
                'is_mobile' => 0,
                'client_type' => 'webshop',
                'implementation_key' => $implementation->key,
            ])
        ));
    }

    /**
     * @param FundRequest $fundRequest
     * @return void
     */
    protected function approveFundRequest(FundRequest $fundRequest): void
    {
        $employee = $fundRequest->fund->organization->employees[0];
        $this->assertNotNull($employee);

        $fundRequest->assignEmployee($employee);
        $fundRequest->refresh();

        $fundRequest->approve();
        $fundRequest->refresh();
    }
}
