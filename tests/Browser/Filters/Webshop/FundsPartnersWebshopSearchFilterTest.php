<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Tag;
use Facebook\WebDriver\Exception\TimeOutException;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Throwable;

class FundsPartnersWebshopSearchFilterTest extends BaseWebshopSearchFilter
{
    /**
     * @return string
     */
    public function getListSelector(): string
    {
        return '@listFunds';
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPartnerFundsPageScopesFundsByOrganization(): void
    {
        $data = $this->makePartnerFundsTestData();

        $this->withPartnerFundsPage($data['implementation'], true, function () use ($data) {
            $this->browse(function (Browser $browser) use ($data) {
                $browser->visit($data['implementation']->urlWebshop('partnerfondsen', [
                    'q' => $data['scope'],
                ]));

                $this->assertListVisibility($browser, $data['partnerFunds'][0]->id, true);
                $this->assertListVisibility($browser, $data['partnerFunds'][1]->id, true);
                $this->assertListVisibility($browser, $data['partnerFunds'][2]->id, true);
                $this->assertListVisibility($browser, $data['ownFund']->id, false);
                $this->assertWebshopRowsCount($browser, 3, '@listFundsContent');

                $browser->visit($data['implementation']->urlWebshop('fondsen', [
                    'q' => $data['scope'],
                ]));

                $this->assertListVisibility($browser, $data['ownFund']->id, true);
                $this->assertListVisibility($browser, $data['partnerFunds'][0]->id, false);
                $this->assertListVisibility($browser, $data['partnerFunds'][1]->id, false);
                $this->assertListVisibility($browser, $data['partnerFunds'][2]->id, false);
                $this->assertWebshopRowsCount($browser, 1, '@listFundsContent');
            });
        }, fn () => $this->deletePartnerFundsTestData($data));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPartnerFundsPageFiltersBySearchOrganizationAndTag(): void
    {
        $data = $this->makePartnerFundsTestData();

        $this->withPartnerFundsPage($data['implementation'], true, function () use ($data) {
            $this->browse(function (Browser $browser) use ($data) {
                $browser->visit($data['implementation']->urlWebshop('partnerfondsen'));

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterQueryValue(
                    $browser,
                    $data['partnerFunds'][0]->name,
                    $data['partnerFunds'][0]->id,
                );

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterByOrganizationsCheckboxes(
                    $browser,
                    $data['partnerFunds'][0]->id,
                    $data['partnerOrganizations'][0],
                );

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterByTagsCheckboxes($browser, $data['partnerFunds'][0]->id, $data['tags'][0]);
            });
        }, fn () => $this->deletePartnerFundsTestData($data));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPartnerFundsPageResetsMultipleSelectedFilters(): void
    {
        $data = $this->makePartnerFundsTestData();

        $this->withPartnerFundsPage($data['implementation'], true, function () use ($data) {
            $this->browse(function (Browser $browser) use ($data) {
                $browser->visit($data['implementation']->urlWebshop('partnerfondsen', [
                    'q' => $data['scope'],
                ]));

                $this->uncollapseWebshopFilterGroup($browser, '@fundFilterGroupOrganizations');
                $this->clearCheckboxFilterItems($browser, '@fundFilterGroupOrganizations');
                $this->toggleFundOrganizationFilter($browser, $data['partnerOrganizations'][0]);
                $this->toggleFundOrganizationFilter($browser, $data['partnerOrganizations'][1]);

                $this->assertListVisibility($browser, $data['partnerFunds'][0]->id, true);
                $this->assertListVisibility($browser, $data['partnerFunds'][1]->id, true);
                $this->assertListVisibility($browser, $data['partnerFunds'][2]->id, false);
                $this->assertWebshopRowsCount($browser, 2, '@listFundsContent');

                $this->assertActiveFilterLabelAndReset(
                    $browser,
                    'organization',
                    $data['partnerOrganizations'][0]->id,
                );

                $this->assertListVisibility($browser, $data['partnerFunds'][0]->id, false);
                $this->assertListVisibility($browser, $data['partnerFunds'][1]->id, true);
                $this->assertWebshopRowsCount($browser, 1, '@listFundsContent');

                $this->toggleFundOrganizationFilter($browser, $data['partnerOrganizations'][0]);
                $this->uncollapseWebshopFilterGroup($browser, '@fundFilterGroupTags');
                $this->clearCheckboxFilterItems($browser, '@fundFilterGroupTags');
                $this->toggleFundTagFilter($browser, $data['tags'][0]);
                $this->toggleFundTagFilter($browser, $data['tags'][1]);

                $this->assertListVisibility($browser, $data['partnerFunds'][0]->id, true);
                $this->assertListVisibility($browser, $data['partnerFunds'][1]->id, true);
                $this->assertListVisibility($browser, $data['partnerFunds'][2]->id, false);
                $this->assertWebshopRowsCount($browser, 2, '@listFundsContent');

                $this->assertActiveFilterLabelAndReset($browser, 'all');

                $this->assertListVisibility($browser, $data['partnerFunds'][0]->id, true);
                $this->assertListVisibility($browser, $data['partnerFunds'][1]->id, true);
                $this->assertListVisibility($browser, $data['partnerFunds'][2]->id, true);
                $this->assertWebshopRowsCount($browser, 3, '@listFundsContent');
            });
        }, fn () => $this->deletePartnerFundsTestData($data));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPartnerFundsPagePersistsFiltersFromQuery(): void
    {
        $data = $this->makePartnerFundsTestData();
        $query = sprintf(
            'organization_ids=%d&organization_ids=%d&tag_ids=%d&tag_ids=%d',
            $data['partnerOrganizations'][0]->id,
            $data['partnerOrganizations'][1]->id,
            $data['tags'][0]->id,
            $data['tags'][1]->id,
        );

        $this->withPartnerFundsPage($data['implementation'], true, function () use ($data, $query) {
            $this->browse(function (Browser $browser) use ($data, $query) {
                $browser->visit($data['implementation']->urlWebshop('partnerfondsen') . "?$query");

                $this->assertPartnerFundsQueryState($browser, $data);
                $browser->refresh();
                $this->assertPartnerFundsQueryState($browser, $data);
            });
        }, fn () => $this->deletePartnerFundsTestData($data));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPartnerFundsPageRedirectsWhenDisabled(): void
    {
        $data = $this->makePartnerFundsTestData();

        $this->withPartnerFundsPage($data['implementation'], false, function () use ($data) {
            $this->browse(function (Browser $browser) use ($data) {
                $browser->visit($data['implementation']->urlWebshop('partnerfondsen'));

                $this->waitForCurrentPath($browser, '/fondsen');
                $browser->waitFor('@listFundsContent');
            });
        }, fn () => $this->deletePartnerFundsTestData($data));
    }

    /**
     * @return array{
     *     implementation: Implementation,
     *     scope: string,
     *     partnerOrganizations: array<int, Organization>,
     *     ownFund: Fund,
     *     partnerFunds: array<int, Fund>,
     *     tags: array<int, Tag>,
     * }
     */
    protected function makePartnerFundsTestData(): array
    {
        $this->deleteStalePartnerFundsTestData();

        $implementation = Implementation::byKey('nijmegen');
        $scope = 'partner-funds-' . Str::random(10);

        $partnerOrganizations = [
            $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
                'name' => "$scope organization 1",
            ]),
            $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
                'name' => "$scope organization 2",
            ]),
            $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
                'name' => "$scope organization 3",
            ]),
        ];

        $ownFund = $this->makeTestFund($implementation->organization, [
            'name' => "$scope own fund",
            'description_text' => "$scope own description text",
            'description_short' => "$scope own description short",
            'order' => -30,
        ], implementation: $implementation);

        $partnerFunds = [
            $this->makeTestFund($partnerOrganizations[0], [
                'name' => "$scope partner fund 1",
                'description_text' => "$scope partner description text 1",
                'description_short' => "$scope partner description short 1",
                'order' => -29,
            ], implementation: $implementation),
            $this->makeTestFund($partnerOrganizations[1], [
                'name' => "$scope partner fund 2",
                'description_text' => "$scope partner description text 2",
                'description_short' => "$scope partner description short 2",
                'order' => -28,
            ], implementation: $implementation),
            $this->makeTestFund($partnerOrganizations[2], [
                'name' => "$scope partner fund 3",
                'description_text' => "$scope partner description text 3",
                'description_short' => "$scope partner description short 3",
                'order' => -27,
            ], implementation: $implementation),
        ];

        $tags = [
            $this->makeAndAppendTestFundTag($partnerFunds[0], "$scope tag 1"),
            $this->makeAndAppendTestFundTag($partnerFunds[1], "$scope tag 2"),
            $this->makeAndAppendTestFundTag($partnerFunds[2], "$scope tag 3"),
        ];

        return compact('implementation', 'scope', 'partnerOrganizations', 'ownFund', 'partnerFunds', 'tags');
    }

    /**
     * @param array $data
     * @return void
     */
    protected function deletePartnerFundsTestData(array $data): void
    {
        foreach ($data['tags'] as $tag) {
            $tag && $this->deleteFundTag($tag);
        }

        foreach (array_merge([$data['ownFund']], $data['partnerFunds']) as $fund) {
            $fund && $this->deleteFund($fund);
        }
    }

    /**
     * @return void
     */
    protected function deleteStalePartnerFundsTestData(): void
    {
        Fund::where('name', 'like', 'partner-funds-%')
            ->get()
            ->each(fn (Fund $fund) => $this->deleteFund($fund));

        Tag::where('key', 'like', 'partner-funds-%')
            ->get()
            ->each(fn (Tag $tag) => $this->deleteFundTag($tag));
    }

    /**
     * @param Tag $tag
     * @return void
     */
    protected function deleteFundTag(Tag $tag): void
    {
        $tag->funds()->detach();
        $tag->delete();
    }

    /**
     * @param Implementation $implementation
     * @param bool $enabled
     * @param callable $callback
     * @param callable|null $finalCallback
     * @throws Throwable
     * @return mixed
     */
    protected function withPartnerFundsPage(
        Implementation $implementation,
        bool $enabled,
        callable $callback,
        callable $finalCallback = null,
    ): mixed {
        return $this->rollbackModels([
            [$implementation, $implementation->only(['show_fund_partners_page'])],
        ], function () use ($implementation, $enabled, $callback) {
            $implementation->forceFill([
                'show_fund_partners_page' => $enabled,
            ])->save();

            Implementation::clearMemo();

            return $callback();
        }, function () use ($finalCallback) {
            $finalCallback && $finalCallback();
            Implementation::clearMemo();
        });
    }

    /**
     * @param Browser $browser
     * @param array $data
     * @throws TimeOutException
     * @return void
     */
    protected function assertPartnerFundsQueryState(Browser $browser, array $data): void
    {
        $this->assertActiveFilterLabelVisible($browser, 'organization', $data['partnerOrganizations'][0]->id);
        $this->assertActiveFilterLabelVisible($browser, 'organization', $data['partnerOrganizations'][1]->id);
        $this->assertActiveFilterLabelVisible($browser, 'tag', $data['tags'][0]->id);
        $this->assertActiveFilterLabelVisible($browser, 'tag', $data['tags'][1]->id);

        $this->assertListVisibility($browser, $data['partnerFunds'][0]->id, true);
        $this->assertListVisibility($browser, $data['partnerFunds'][1]->id, true);
        $this->assertListVisibility($browser, $data['partnerFunds'][2]->id, false);
        $this->assertWebshopRowsCount($browser, 2, '@listFundsContent');
    }

    /**
     * @param Browser $browser
     * @param string $path
     * @throws TimeoutException
     * @return void
     */
    protected function waitForCurrentPath(Browser $browser, string $path): void
    {
        $browser->waitUsing(null, 100, function () use ($browser, $path) {
            $url = $browser->driver->getCurrentURL();
            $fragment = parse_url($url, PHP_URL_FRAGMENT);
            $fragmentPath = $fragment ? parse_url(ltrim($fragment, '!'), PHP_URL_PATH) : null;

            return parse_url($url, PHP_URL_PATH) === $path || $fragmentPath === $path;
        }, "Timed out waiting for current path to be $path.");
    }
}
