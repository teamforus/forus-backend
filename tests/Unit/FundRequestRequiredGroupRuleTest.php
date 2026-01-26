<?php

namespace Tests\Unit;

use App\Rules\FundRequests\FundRequestRecords\FundRequestRequiredGroupRule;
use App\Traits\DoesTesting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\CreatesApplication;
use Tests\TestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;

class FundRequestRequiredGroupRuleTest extends TestCase
{
    use DoesTesting;
    use DatabaseTransactions;
    use CreatesApplication;
    use MakesTestFundRequests;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @return void
     */
    public function testRequiredGroupFailsWithEmptyValues(): void
    {
        $identity = $this->makeIdentity(email: $this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);

        $group = $this->makeCriteriaGroup($fund, required: true);
        $criterion = $fund->criteria()->create([
            'record_type_key' => 'group_key_one',
            'operator' => '*',
            'value' => '',
            'show_attachment' => false,
            'optional' => true,
            'fund_criteria_group_id' => $group->id,
        ]);

        $rule = new FundRequestRequiredGroupRule($fund, null, [
            $criterion->id => '',
        ]);

        $this->assertFalse($rule->passes("criteria_groups.$group->id", $group->id));
    }

    /**
     * @return void
     */
    public function testRequiredGroupPassesWithValue(): void
    {
        $identity = $this->makeIdentity(email: $this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);

        $group = $this->makeCriteriaGroup($fund, required: true);
        $criterion = $fund->criteria()->create([
            'record_type_key' => 'group_key_two',
            'operator' => '*',
            'value' => '',
            'show_attachment' => false,
            'optional' => true,
            'fund_criteria_group_id' => $group->id,
        ]);

        $rule = new FundRequestRequiredGroupRule($fund, null, [
            $criterion->id => 'filled',
        ]);

        $this->assertTrue($rule->passes("criteria_groups.$group->id", $group->id));
    }

    /**
     * @return void
     */
    public function testRequiredGroupFailsWithZeroValue(): void
    {
        $identity = $this->makeIdentity(email: $this->makeUniqueEmail());
        $organization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($organization);
        $group = $this->makeCriteriaGroup($fund, required: true);

        $criterion = $fund->criteria()->create([
            'record_type_key' => 'group_key_zero',
            'operator' => '*',
            'value' => '',
            'show_attachment' => false,
            'optional' => true,
            'fund_criteria_group_id' => $group->id,
        ]);

        $rule = new FundRequestRequiredGroupRule($fund, null, [
            $criterion->id => '0',
        ]);

        $this->assertFalse($rule->passes("criteria_groups.$group->id", $group->id));
    }
}
