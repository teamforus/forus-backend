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
use Throwable;

class FundPreCheckWebshopSearchFilterTest extends BaseWebshopSearchFilter
{
    /**
     * @return string
     */
    public function getListSelector(): string
    {
        return '@listFundsPreCheck';
    }

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

                $this->fillListSearchForEmptyResults($browser);
                $this->assertFundsSearchIsWorking($browser, $fund);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterByOrganization($browser, $fund->organization, $fund->id, 1);

                $this->fillListSearchForEmptyResults($browser);
                $this->assertListFilterByTag($browser, $fund->tags[0], $fund->id, 1);

                $this->logout($browser);
            });
        }, function () use ($implementation, $fund, $fund2) {
            $implementation->funds->each(fn (Fund $fund) => $fund->update([
                'archived' => false,
            ]));

            $this->deleteFund($fund);
            $this->deleteFund($fund2);
        });
    }

    /**
     * @param Implementation $implementation
     * @return Fund
     */
    protected function makeFundAndCriteria(Implementation $implementation): Fund
    {
        $organization = $this->makeTestOrganization($this->makeIdentity(), ['allow_pre_checks' => true]);
        $fund = $this->makeTestFund($organization, implementation: $implementation);

        $this->makeAndAppendTestFundTag($fund);

        // add criteria
        $recordType = RecordType::create([
            'key' => token_generator()->generate(16),
            'type' => RecordType::TYPE_NUMBER,
            'criteria' => true,
            'pre_check' => true,
            'control_type' => RecordType::CONTROL_TYPE_NUMBER,
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
     * @throws TimeOutException
     * @return void
     */
    protected function assertFundsSearchIsWorking(Browser $browser, Fund $fund): void
    {
        $this->assertListFilterQueryValue($browser, $fund->name, $fund->id);
        $this->assertListFilterQueryValue($browser, $fund->organization->name, $fund->id);
        $this->assertListFilterQueryValue($browser, $fund->description_text, $fund->id);
        $this->assertListFilterQueryValue($browser, $fund->description_short, $fund->id);
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
                RecordType::CONTROL_TYPE_NUMBER => '@controlNumber' . $record_key,
                RecordType::CONTROL_TYPE_TEXT => '@controlText' . $record_key,
            };

            $browser->waitFor($selector);
            $browser->typeSlowly($selector, 0);
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
