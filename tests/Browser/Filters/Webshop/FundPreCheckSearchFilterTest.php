<?php

namespace Tests\Browser\Filters\Webshop;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\Implementation;
use App\Models\RecordType;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFunds;
use Throwable;

class FundPreCheckSearchFilterTest extends DuskTestCase
{
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;

    /**
     * @throws Throwable
     * @return void
     */
    public function testFundPreCheckFilter(): void
    {
        $implementation = Implementation::byKey('nijmegen');
        $identity = $this->makeIdentity($this->makeUniqueEmail());

        $implementation->funds->each(fn (Fund $fund) => $fund->update([
            'archived' => true,
        ]));

        $fund = $this->makeFundAndCriteria($implementation);
        $fund2 = $this->makeFundAndCriteria($implementation);

        $criteria = $fund->criteria->merge($fund2->criteria);

        $this->rollbackModels([], function () use ($implementation, $fund, $identity, $criteria) {
            $this->browse(function (Browser $browser) use ($implementation, $fund, $identity, $criteria) {
                $browser->visit($implementation->urlWebshop('fondsen'));

                $this->loginIdentity($browser, $identity);
                $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);
                $this->goToIdentityVouchers($browser);

                $browser->visit($implementation->urlWebshop('regelingencheck'));

                $this->fillPreCheckForm($browser, $criteria);

                $this->assertFundsSearchIsWorking($browser, $fund)
                    ->fillSearchForEmptyResults($browser)
                    ->assertFundsFilterByOrganization($browser, $fund)
                    ->fillSearchForEmptyResults($browser)
                    ->assertFundsFilterByTag($browser, $fund);

                $this->logout($browser);
            });
        }, function () use ($implementation, $fund, $fund2) {
            $implementation->funds->each(fn (Fund $fund) => $fund->update([
                'archived' => false,
            ]));

            $fund && $this->deleteFund($fund);
            $fund2 && $this->deleteFund($fund2);
        });
    }

    /**
     * @param Implementation $implementation
     * @return Fund
     */
    protected function makeFundAndCriteria(Implementation $implementation): Fund
    {
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()), [
            'allow_pre_checks' => true,
        ]);

        $fund = $this->makeTestFund($organization, [
            'description_text' => $this->faker->sentence,
            'description_short' => $this->faker->sentence,
        ], [
            'implementation_id' => $implementation->id,
        ]);

        $fund->tags()->firstOrCreate([
            'key' => $this->faker->slug,
            'scope' => 'webshop',
        ])->translateOrNew(app()->getLocale())->fill([
            'name' => $this->faker->name,
        ])->save();

        // add criteria
        $recordType = RecordType::create([
            'key' => token_generator()->generate(16),
            'type' => 'number',
            'criteria' => true,
            'control_type' => 'number',
            'pre_check' => true,
        ]);

        $criterion = [
            'title' => "Choose item $recordType->key",
            'value' => '',
            'operator' => '*',
            'description' => "Choose item $recordType->key description",
            'record_type_key' => $recordType->key,
            'show_attachment' => false,
        ];

        $fund->criteria()->delete();
        $this->makeFundCriteria($fund, [$criterion]);

        return $fund;
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return FundPreCheckSearchFilterTest
     */
    protected function assertFundsFilterByOrganization(Browser $browser, Fund $fund): static
    {
        $browser->waitFor('@selectControlOrganizations');
        $browser->click('@selectControlOrganizations .select-control-search');
        $this->findOptionElement($browser, '@selectControlOrganizations', $fund->organization->name)->click();

        return $this->assertFundVisible($browser, $fund);
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return FundPreCheckSearchFilterTest
     */
    protected function assertFundsFilterByTag(Browser $browser, Fund $fund): static
    {
        $browser->waitFor('@selectControlTags');
        $browser->click('@selectControlTags .select-control-search');
        $this->findOptionElement($browser, '@selectControlTags', $fund->tags()->first()->name)->click();

        return $this->assertFundVisible($browser, $fund);
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @throws TimeOutException
     * @return FundPreCheckSearchFilterTest
     */
    protected function assertFundsSearchIsWorking(Browser $browser, Fund $fund): static
    {
        return $this
            ->assertSearch($browser, $fund, $fund->name)
            ->fillSearchForEmptyResults($browser)
            ->assertSearch($browser, $fund, $fund->organization->name)
            ->fillSearchForEmptyResults($browser)
            ->assertSearch($browser, $fund, $fund->description_text)
            ->fillSearchForEmptyResults($browser)
            ->assertSearch($browser, $fund, $fund->description_short)
            ->fillSearchForEmptyResults($browser);
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @param string $q
     * @throws TimeoutException
     * @return FundPreCheckSearchFilterTest
     */
    protected function assertSearch(Browser $browser, Fund $fund, string $q): static
    {
        $this->searchWebshopList($browser, '@listFundsPreCheck', $q, $fund->id);
        $this->clearField($browser, '@listFundsPreCheckSearch');

        return $this;
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return FundPreCheckSearchFilterTest
     */
    protected function fillSearchForEmptyResults(Browser $browser): static
    {
        $this->searchWebshopList($browser, '@listFundsPreCheck', '###############', null, 0);
        $this->clearField($browser, '@listFundsPreCheckSearch');

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @param int $count
     * @throws TimeoutException
     * @return FundPreCheckSearchFilterTest
     */
    protected function assertFundVisible(Browser $browser, Fund $fund, int $count = 1): static
    {
        $browser->waitFor("@listFundsPreCheckRow$fund->id");
        $browser->assertVisible("@listFundsPreCheckRow$fund->id");
        $this->assertWebshopRowsCount($browser, $count, '@listFundsPreCheckContent');

        return $this;
    }

    /**
     * @param Browser $browser
     * @param Collection|FundCriterion[] $criteria
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    protected function fillPreCheckForm(Browser $browser, Collection|array $criteria): void
    {
        foreach ($criteria as $criterion) {
            $record_key = $criterion->record_type_key;

            $selector = match ($criterion->record_type->control_type) {
                'number' => '@controlNumber' . $record_key,
                'text' => '@controlText' . $record_key,
            };

            $browser->waitFor($selector);
            $browser->typeSlowly($selector, 50);
        }

        $browser->click('@submitBtn');
    }

    /**
     * @param Browser $browser
     * @param int $count
     * @param string $selector
     * @param string $operator
     * @return void
     */
    protected function assertWebshopRowsCount(
        Browser $browser,
        int $count,
        string $selector,
        string $operator = '=',
    ): void {
        $compare = match ($operator) {
            '=' => fn ($a, $b) => $a == $b,
            '>' => fn ($a, $b) => $a > $b,
            '>=' => fn ($a, $b) => $a >= $b,
            '<' => fn ($a, $b) => $a < $b,
            '<=' => fn ($a, $b) => $a <= $b,
            default => throw new InvalidArgumentException("Invalid operator \"$operator\""),
        };

        $browser->within($selector, function (Browser $browser) use ($count, $operator, $compare) {
            if ($count === 0 && $operator === '=') {
                $browser->waitUntilMissing($this->getWebshopRowsSelector());
            } else {
                $browser->waitUsing(null, 100, function () use ($browser, $compare, $count) {
                    return $compare(count($browser->elements($this->getWebshopRowsSelector())), $count);
                });
            }
        });
    }
}
