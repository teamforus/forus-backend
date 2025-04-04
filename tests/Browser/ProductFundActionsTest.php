<?php

namespace Tests\Browser;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Throwable;

class ProductFundActionsTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesTestFundRequests;

    /**
     * Assert available fund actions on product page: reserve product, make fund request, fund activate,
     * go to fund requests, go to voucher page, external link, not available text.
     * @throws Throwable
     */
    public function testProductFundActions()
    {
        // Select implementation
        $implementation = Implementation::byKey('nijmegen');

        $fundConfigs = $this->getVoucherFundSettings();
        $payoutFundConfigs = $this->getPayoutFundSettings();

        $fund = $this->createFund($implementation->organization, $fundConfigs);
        $fund2 = $this->createFund($implementation->organization, $fundConfigs);
        $fundPayout = $this->createFund($implementation->organization, $payoutFundConfigs);

        $this->rollbackModels([], function () use ($implementation, $fund, $fund2, $fundPayout) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $fund2, $fundPayout) {
                $fundConfigs = $this->getVoucherFundSettings();
                $payoutFundConfigs = $this->getPayoutFundSettings();

                $requester = $this->makeIdentity($this->makeUniqueEmail());

                $browser->visit($implementation->urlWebshop())->waitFor('@headerTitle');
                $this->loginIdentity($browser, $requester);

                $product = $this->makeProductsFundFund(1)[0];
                $this->addProductFundToFund($fund, $product, false);

                $browser->visit($implementation->urlWebshop("products/$product->id"));
                $browser->waitFor('@productName')->assertSeeIn('@productName', $product->name);

                // assert only fund request button present
                $browser->waitFor("@fundItem$fund->id");
                $browser->within("@fundItem$fund->id", function (Browser $browser) {
                    $browser->assertPresent('@fundRequest');
                    $browser->assertMissing('@reserveProduct');
                    $browser->assertMissing('@fundRequests');
                    $browser->assertMissing('@fundActivate');
                    $browser->assertMissing('@pendingButton');
                    $browser->assertMissing('@voucherButton');
                    $browser->assertMissing('@externalLink');
                    $browser->assertMissing('@notAvailable');
                });

                // create a fund request and assert only fund requests button present
                $fundRequest = $this->setCriteriaAndMakeFundRequest($requester, $fund, $fundConfigs['requester_records']);

                $browser->refresh();
                $browser->waitFor('@productName')->assertSeeIn('@productName', $product->name);
                $browser->waitFor("@fundItem$fund->id");

                $browser->within("@fundItem$fund->id", function (Browser $browser) {
                    $browser->assertMissing('@fundRequest');
                    $browser->assertMissing('@reserveProduct');
                    $browser->assertPresent('@fundRequests');
                    $browser->assertMissing('@fundActivate');
                    $browser->assertMissing('@pendingButton');
                    $browser->assertMissing('@voucherButton');
                    $browser->assertMissing('@externalLink');
                    $browser->assertMissing('@notAvailable');
                });

                // approve fund request and assert only reserve product button present
                $this->approveFundRequest($fundRequest);

                $browser->refresh();
                $browser->waitFor('@productName')->assertSeeIn('@productName', $product->name);
                $browser->waitFor("@fundItem$fund->id");

                $browser->within("@fundItem$fund->id", function (Browser $browser) {
                    $browser->assertMissing('@fundRequest');
                    $browser->assertPresent('@reserveProduct');
                    $browser->assertMissing('@fundRequests');
                    $browser->assertMissing('@fundActivate');
                    $browser->assertMissing('@pendingButton');
                    $browser->assertMissing('@voucherButton');
                    $browser->assertMissing('@externalLink');
                    $browser->assertMissing('@notAvailable');
                });

                // Create fund with same criteria
                $this->addProductFundToFund($fund2, $product, false);
                $browser->visit($implementation->urlWebshop())->refresh();

                // Assert only activate button is present
                $browser->visit($implementation->urlWebshop("products/$product->id"));
                $browser->waitFor('@productName')->assertSeeIn('@productName', $product->name);
                $browser->waitFor("@fundItem$fund2->id");

                $browser->within("@fundItem$fund2->id", function (Browser $browser) {
                    $browser->assertMissing('@fundRequest');
                    $browser->assertMissing('@reserveProduct');
                    $browser->assertMissing('@fundRequests');
                    $browser->assertPresent('@fundActivate');
                    $browser->assertMissing('@pendingButton');
                    $browser->assertMissing('@voucherButton');
                    $browser->assertMissing('@externalLink');
                    $browser->assertMissing('@notAvailable');
                });

                // not available text present for this fund
                $fundRequest = $this->setCriteriaAndMakeFundRequest($requester, $fundPayout, $payoutFundConfigs['requester_records']);

                $this->approveFundRequest($fundRequest);
                $this->addProductFundToFund($fundPayout, $product, false);

                $browser->refresh();
                $browser->waitFor('@productName')->assertSeeIn('@productName', $product->name);
                $browser->waitFor("@fundItem$fundPayout->id");

                $browser->within("@fundItem$fundPayout->id", function (Browser $browser) {
                    $browser->assertMissing('@fundRequest');
                    $browser->assertMissing('@reserveProduct');
                    $browser->assertMissing('@fundRequests');
                    $browser->assertMissing('@fundActivate');
                    $browser->assertMissing('@pendingButton');
                    $browser->assertMissing('@voucherButton');
                    $browser->assertMissing('@externalLink');
                    $browser->assertPresent('@notAvailable');
                });

                // set sold out product and assert only open voucher button visible for fund
                $product->update([ 'total_amount' => 0 ]);
                $product->updateSoldOutState();

                $browser->refresh();
                $browser->waitFor('@productName')->assertSeeIn('@productName', $product->name);
                $browser->waitFor("@fundItem$fund->id");

                $browser->within("@fundItem$fund->id", function (Browser $browser) {
                    $browser->assertMissing('@fundRequest');
                    $browser->assertMissing('@reserveProduct');
                    $browser->assertMissing('@fundRequests');
                    $browser->assertMissing('@fundActivate');
                    $browser->assertMissing('@pendingButton');
                    $browser->assertPresent('@voucherButton');
                    $browser->assertMissing('@externalLink');
                    $browser->assertMissing('@notAvailable');
                });

                // add external link attributes to fund and assert also external link is visible
                $btnText = $this->faker->word();

                $fund->update([
                    'external_link_text' => $btnText,
                    'external_link_url' => $this->faker->url(),
                ]);

                $browser->refresh();
                $browser->waitFor('@productName')->assertSeeIn('@productName', $product->name);
                $browser->waitFor("@fundItem$fund->id");

                $browser->within("@fundItem$fund->id", function (Browser $browser) use ($btnText) {
                    $browser->assertMissing('@fundRequest');
                    $browser->assertMissing('@reserveProduct');
                    $browser->assertMissing('@fundRequests');
                    $browser->assertMissing('@fundActivate');
                    $browser->assertMissing('@pendingButton');
                    $browser->assertPresent('@voucherButton');
                    $browser->assertPresent('@externalLink')->assertSeeIn('@externalLink', $btnText);
                    $browser->assertMissing('@notAvailable');
                });
            });
        }, function () use ($fund, $fund2, $fundPayout) {
            $fund && $this->deleteFund($fund);
            $fund2 && $this->deleteFund($fund2);
            $fundPayout && $this->deleteFund($fundPayout);
        });
    }

    /**
     * @param Organization $organization
     * @param array $settings
     * @return Fund
     */
    protected function createFund(Organization $organization, array $settings): Fund
    {
        return $this
            ->makeTestFund($organization, [], $settings['fund_config'])
            ->syncCriteria($settings['fund_criteria'] ?? [])
            ->refresh();
    }

    /**
     * @param Identity $requester
     * @param Fund $fund
     * @param array $records
     * @return FundRequest
     */
    protected function setCriteriaAndMakeFundRequest(Identity $requester, Fund $fund, array $records): FundRequest
    {
        $recordsList = collect($records)->map(function (string|int $value, string $key) use ($fund) {
            return $this->makeRequestCriterionValue($fund, $key, $value);
        });

        $response = $this->makeFundRequest($requester, $fund, $recordsList, false);
        $response->assertSuccessful();

        $fundRequest = FundRequest::find($response->json('data.id'));
        $this->assertNotNull($fundRequest);

        return $fundRequest;
    }

    /**
     * @return array
     */
    protected function getPayoutFundSettings(): array
    {
        return [
            'fund_config' => [
                'outcome_type' => 'payout',
                'iban_record_key' => 'iban',
                'iban_name_record_key' => 'iban_name',
            ],
            'fund_criteria' => [[
                'record_type_key' => 'children_nth',
                'operator' => '>',
                'value' => 2,
                'show_attachment' => false,
            ], [
                'record_type_key' => 'iban',
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
            ], [
                'record_type_key' => 'iban_name',
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
            ]],
            'requester_records' => [
                'iban' => $this->faker->iban(),
                'iban_name' => $this->faker->firstName(),
                'children_nth' => 3,
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getVoucherFundSettings(): array
    {
        return [
            'fund_config' => [
                'outcome_type' => 'voucher',
            ],
            'fund_criteria' => [[
                'record_type_key' => 'children_nth',
                'operator' => '>',
                'value' => 2,
                'show_attachment' => false,
            ]],
            'requester_records' => [
                'children_nth' => 3,
            ],
        ];
    }

    /**
     * @param FundRequest $fundRequest
     * @return void
     */
    private function approveFundRequest(FundRequest $fundRequest): void
    {
        $employee = $fundRequest->fund->organization->employees[0];
        $this->assertNotNull($employee);

        $fundRequest->assignEmployee($employee);
        $fundRequest->refresh();

        $fundRequest->approve();
        $fundRequest->refresh();
    }
}
