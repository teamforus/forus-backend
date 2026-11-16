<?php

namespace Tests\Browser\Filters\Dashboards;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Permission;
use App\Models\Product;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeoutException;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestHousehold;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class GlobalDashboardSearchFilterTest extends DuskTestCase
{
    use MakesTestFunds;
    use MakesTestVouchers;
    use HasFrontendActions;
    use MakesTestHousehold;
    use RollbackModelsTrait;
    use MakesTestFundRequests;
    use MakesProductReservations;

    /**
     * Assert each sponsor block finds its entity by a main property and by id,
     * opens the list page with the search query.
     *
     * @throws Throwable
     * @return void
     */
    public function testSponsorDashboardSearch(): void
    {
        $data = $this->prepareSponsorSearchData();

        /** @var Organization $sponsor */
        $sponsor = $data['sponsor'];

        $this->rollbackModels([], function () use ($data, $sponsor) {
            $this->browse(function (Browser $browser) use ($data, $sponsor) {
                $this->signInDashboard($browser, 'sponsor', $sponsor->identity, $sponsor);

                $this->assertSearchBlockWorks($browser, 'identities', $data['person']->id, [
                    $data['given_name'],
                    (string) $data['person']->id,
                ], $data['given_name'], '@tableProfilesRow');

                $this->assertSearchBlockWorks($browser, 'households', $data['household']->id, [
                    $data['household']->street,
                    (string) $data['household']->id,
                ], $data['household']->street, '@tableProfilesRow');

                $this->assertSearchBlockWorks($browser, 'providers', $data['provider']->id, [
                    $data['provider']->name,
                    (string) $data['provider']->id,
                ], $data['provider']->name, '@tableProviderRow');

                $this->assertSearchBlockWorks($browser, 'products', $data['product']->id, [
                    $data['product']->name,
                    (string) $data['product']->id,
                ], $data['product']->name, '@tableSponsorProductRow');

                $this->assertSearchBlockWorks($browser, 'vouchers', $data['voucher']->id, [
                    $data['voucher']->number,
                    (string) $data['voucher']->id,
                ], $data['voucher']->number, '@tableVoucherRow');

                $this->assertSearchBlockWorks($browser, 'fund_requests', $data['fund_request']->id, [
                    $data['requester']->email,
                    (string) $data['fund_request']->id,
                ], $data['requester']->email, '@tableFundRequestRow');

                $this->logout($browser);
            });
        }, function () use ($data) {
            $this->deleteFund($data['fund']);
            $data['household']->delete();
            $data['product']->delete();
        });
    }

    /**
     * Assert blocks are hidden when the employee has no permission or the organization feature is disabled.
     *
     * @throws Throwable
     * @return void
     */
    public function testSponsorDashboardSearchPermissions(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
            'allow_profiles' => true,
        ]);

        $fund = $this->makeTestFund($sponsor);
        $person = $this->makeIdentity($this->makeUniqueEmail('global_search_person'));
        $voucher = $this->makeTestVoucher($fund, $person);
        $employee = $this->makeTestEmployeeWithPermissions($sponsor, [Permission::VIEW_VOUCHERS]);

        $this->rollbackModels([], function () use ($sponsor, $person, $voucher, $employee) {
            $this->browse(function (Browser $browser) use ($sponsor, $person, $voucher, $employee) {
                // the email matches both the person and the voucher
                $this->signInDashboard($browser, 'sponsor', $sponsor->identity, $sponsor);
                $this->searchHeader($browser, $person->email);
                $this->assertHeaderSearchItem($browser, 'identities', $person->id, $person->email);
                $this->assertHeaderSearchItem($browser, 'vouchers', $voucher->id, $voucher->number);
                $this->logout($browser);

                // employee can only view vouchers
                $this->signInDashboard($browser, 'sponsor', $employee->identity, $sponsor);
                $this->searchHeader($browser, $person->email);
                $this->assertHeaderSearchItem($browser, 'vouchers', $voucher->id, $voucher->number);
                $this->assertHeaderSearchBlockMissing($browser, 'identities');
                $this->logout($browser);

                // profiles are disabled for the organization
                $sponsor->forceFill(['allow_profiles' => false])->save();

                $this->signInDashboard($browser, 'sponsor', $sponsor->identity, $sponsor);
                $this->searchHeader($browser, $person->email);
                $this->assertHeaderSearchItem($browser, 'vouchers', $voucher->id, $voucher->number);
                $this->assertHeaderSearchBlockMissing($browser, 'identities');
                $this->logout($browser);
            });
        }, function () use ($fund) {
            $this->deleteFund($fund);
        });
    }

    /**
     * Assert each provider block finds its entity, opens the list page.
     *
     * @throws Throwable
     * @return void
     */
    public function testProviderDashboardSearch(): void
    {
        $data = $this->prepareProviderSearchData();

        /** @var Organization $provider */
        $provider = $data['provider'];

        $this->rollbackModels([], function () use ($data, $provider) {
            $this->browse(function (Browser $browser) use ($data, $provider) {
                $this->signInDashboard($browser, 'provider', $provider->identity, $provider);

                $this->assertSearchBlockWorks($browser, 'products', $data['product']->id, [
                    $data['product']->name,
                    (string) $data['product']->id,
                ], $data['product']->name, '@tableProductRow');

                $this->assertSearchBlockWorks($browser, 'reservations', $data['reservation']->id, [
                    $data['reservation']->code,
                    $data['first_name'],
                ], $data['reservation']->code, '@tableReservationRow');

                $this->logout($browser);
            });
        }, function () use ($data) {
            $this->deleteProviderSearchData($data);
        });
    }

    /**
     * Assert the reservations block is hidden for an employee who can only manage products.
     *
     * @throws Throwable
     * @return void
     */
    public function testProviderDashboardSearchPermissions(): void
    {
        $data = $this->prepareProviderSearchData();

        /** @var Organization $provider */
        $provider = $data['provider'];
        $employee = $this->makeTestEmployeeWithPermissions($provider, [Permission::MANAGE_PRODUCTS]);

        $this->rollbackModels([], function () use ($data, $provider, $employee) {
            $this->browse(function (Browser $browser) use ($data, $provider, $employee) {
                // the product name matches both the product and the reservation
                $this->signInDashboard($browser, 'provider', $provider->identity, $provider);
                $this->searchHeader($browser, $data['product']->name);
                $this->assertHeaderSearchItem($browser, 'products', $data['product']->id, $data['product']->name);
                $this->assertHeaderSearchItem($browser, 'reservations', $data['reservation']->id, $data['reservation']->code);
                $this->logout($browser);

                $this->signInDashboard($browser, 'provider', $employee->identity, $provider);
                $this->searchHeader($browser, $data['product']->name);
                $this->assertHeaderSearchItem($browser, 'products', $data['product']->id, $data['product']->name);
                $this->assertHeaderSearchBlockMissing($browser, 'reservations');
                $this->logout($browser);
            });
        }, function () use ($data) {
            $this->deleteProviderSearchData($data);
        });
    }

    /**
     * Assert the "no results" view, the clear button and closing the results with Escape.
     *
     * @throws Throwable
     * @return void
     */
    public function testDashboardSearchEmptyAndClear(): void
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $fund = $this->makeTestFund($sponsor);
        $voucher = $this->makeTestVoucher($fund, $this->makeIdentity($this->makeUniqueEmail()));

        $this->rollbackModels([], function () use ($sponsor, $voucher) {
            $this->browse(function (Browser $browser) use ($sponsor, $voucher) {
                $this->signInDashboard($browser, 'sponsor', $sponsor->identity, $sponsor);

                // no results
                $query = 'nomatch' . Str::lower(Str::random(10));

                $this->searchHeader($browser, $query);
                $browser->waitFor('@headerSearchEmpty');
                $browser->assertSeeIn('@headerSearchEmpty', "Geen resultaten voor \"$query\".");

                // clear button empties the input and closes the results
                $browser->click('@headerSearchClear');
                $browser->waitUntilMissing('@headerSearchResults');
                $browser->assertInputValue('@headerSearchInput', '');

                // escape closes the results
                $this->searchHeader($browser, $voucher->number);
                $this->assertHeaderSearchItem($browser, 'vouchers', $voucher->id, $voucher->number);
                $browser->keys('@headerSearchInput', '{escape}');
                $browser->waitUntilMissing('@headerSearchResults');

                $this->logout($browser);
            });
        }, function () use ($fund) {
            $this->deleteFund($fund);
        });
    }

    /**
     * @return array
     */
    protected function prepareSponsorSearchData(): array
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
            'allow_profiles' => true,
            'allow_profiles_households' => true,
            'allow_product_updates' => true,
        ]);

        $fund = $this->makeTestFund($sponsor, fundConfigsData: [
            'allow_fund_requests' => true,
        ]);

        // person (related to the sponsor by the voucher) and voucher
        $givenName = 'global_search_person' . Str::lower(Str::random(8));
        $person = $this->makeIdentity($this->makeUniqueEmail());
        $voucher = $this->makeTestVoucher($fund, $person);

        $sponsor->findOrMakeProfile($person)->updateRecords([
            'given_name' => $givenName,
            'family_name' => 'Tester',
        ]);

        // household
        $household = $this->makeTestHousehold($sponsor, data: [
            'street' => 'global_search_street' . Str::lower(Str::random(8)),
            'house_nr' => '12',
            'city' => 'Nijmegen',
        ]);

        // provider (accepted, so it is shown on the "active" tab the block links to) and product
        $provider = $this->makeTestProviderOrganization($this->makeIdentity($this->makeUniqueEmail()), [
            'name' => 'global_search_provider' . Str::lower(Str::random(8)),
        ]);

        $this->makeTestFundProvider($provider, $fund);

        $product = $this->makeTestProduct($provider, attributes: [
            'name' => 'global_search_product' . Str::lower(Str::random(8)),
        ]);

        // fund request
        $requester = $this->makeIdentity($this->makeUniqueEmail('global_search_requester'));

        $fundRequest = $this->setCriteriaAndMakeFundRequest($requester, $fund, [
            'children_nth' => 3,
        ]);

        return [
            'sponsor' => $sponsor,
            'fund' => $fund,
            'given_name' => $givenName,
            'person' => $person,
            'voucher' => $voucher,
            'household' => $household,
            'provider' => $provider,
            'product' => $product,
            'requester' => $requester,
            'fund_request' => $fundRequest,
        ];
    }

    /**
     * @throws Throwable
     * @return array
     */
    protected function prepareProviderSearchData(): array
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $fund = $this->makeTestFund($sponsor);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $product = $this->createProductForReservation($provider, [$fund]);
        $product->update(['name' => 'global_search_product' . Str::lower(Str::random(8))]);

        $firstName = 'gs_client_' . Str::lower(Str::random(8));
        $voucher = $this->makeTestVoucher($fund, $this->makeIdentity($this->makeUniqueEmail()));

        $reservation = $voucher->reserveProduct(product: $product, extraData: [
            'first_name' => $firstName,
            'last_name' => 'Tester',
            'user_note' => '',
        ]);

        return [
            'fund' => $fund,
            'provider' => $provider,
            'product' => $product,
            'first_name' => $firstName,
            'reservation' => $reservation,
        ];
    }

    /**
     * @param array{fund: Fund, product: Product} $data
     * @return void
     */
    protected function deleteProviderSearchData(array $data): void
    {
        $this->deleteFund($data['fund']);
        $data['product']->delete();
    }

    /**
     * @param Browser $browser
     * @param string $type
     * @param Identity $identity
     * @param Organization $organization
     *@throws TimeoutException
     * @return void
     */
    protected function signInDashboard(
        Browser $browser,
        string $type,
        Identity $identity,
        Organization $organization,
    ): void {
        $url = match($type) {
            'sponsor' => Implementation::general()->urlSponsorDashboard(),
            'provider' => Implementation::general()->urlProviderDashboard(),
        };

        $browser->visit($url);

        $this->loginIdentity($browser, $identity);

        if ($organization->identity_address === $identity->address) {
            match($type) {
                'sponsor' => $this->assertIdentityAuthenticatedOnSponsorDashboard($browser, $identity),
                'provider' => $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $identity),
            };
        } else {
            $browser->waitFor('@identityEmail');
            $browser->assertSeeIn('@identityEmail', $identity->email);
        }

        $this->selectDashboardOrganization($browser, $organization);

        $browser->waitFor('@headerSearchInput');
    }

    /**
     * @param Browser $browser
     * @param string $block
     * @param int $id
     * @param string[] $queries
     * @param string $itemText
     * @param string $listRowSelector
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function assertSearchBlockWorks(
        Browser $browser,
        string $block,
        int $id,
        array $queries,
        string $itemText,
        string $listRowSelector,
    ): void {
        foreach ($queries as $query) {
            $this->searchHeader($browser, $query);
            $this->assertHeaderSearchItem($browser, $block, $id, $itemText);
        }

        // open the list with the last query and assert the entity is shown
        $browser->click("@headerSearchBlockLink_$block");
        $browser->waitFor("$listRowSelector$id");
    }

    /**
     * @param Browser $browser
     * @param string $query
     * @throws TimeoutException
     * @return void
     */
    protected function searchHeader(Browser $browser, string $query): void
    {
        $browser->waitFor('@headerSearchInput');
        $this->typeSearchInput($browser, '@headerSearchInput', $query);

        $browser->waitUsing(null, 100, function () use ($browser, $query) {
            return
                $browser->attribute('@headerSearchResults', 'data-query') === trim($query) &&
                $browser->attribute('@headerSearchResults', 'aria-busy') === 'false';
        });
    }

    /**
     * @param Browser $browser
     * @param string $block
     * @param int $id
     * @param string $text
     * @throws TimeoutException
     * @return void
     */
    protected function assertHeaderSearchItem(Browser $browser, string $block, int $id, string $text): void
    {
        $browser->waitFor("@headerSearchItem_{$block}_$id");
        $browser->assertSeeIn("@headerSearchItem_{$block}_$id", $text);
    }

    /**
     * @param Browser $browser
     * @param string $block
     * @return void
     */
    protected function assertHeaderSearchBlockMissing(Browser $browser, string $block): void
    {
        $browser->assertMissing("@headerSearchBlock_$block");
    }
}
