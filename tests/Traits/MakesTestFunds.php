<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;

trait MakesTestFunds
{
    /**
     * @param Organization $organization
     * @param array $fundData
     * @param array $fundConfigsData
     * @return Fund
     * @throws \Throwable
     */
    protected function makeTestFund(
        Organization $organization,
        array $fundData = [],
        array $fundConfigsData = [],
    ): Fund {
        /** @var Fund $fund */
        $fund = $organization->funds()->create([
            'name' => fake()->text(30),
            'start_date' => now()->subDay(),
            'end_date' => now()->addYear(),
            'criteria_editable_after_start' => true,
            'type' => Fund::TYPE_BUDGET,
            ...$fundData,
        ]);

        $fund->changeState($fund::STATE_ACTIVE);

        $implementation = $organization->implementations->isNotEmpty() ?
            $organization->implementations[0]->id :
            $this->makeTestImplementation($organization);

        $fund->fund_config()->forceCreate([
            'key' => str_slug(token_generator()->generate(4, 4)),
            'implementation_id' => $implementation->id,
            'is_configured' => true,
            'email_required' => true,
            'allow_fund_requests' => true,
            'allow_prevalidations' => true,
            'allow_direct_requests' => true,
            'csv_primary_key' => 'uid',
            ...$fundConfigsData,
        ]);

        $fund->syncDescriptionMarkdownMedia('cms_media');

        if ($fundData['criteria'] ?? false) {
            $fund->syncCriteria($fundData['criteria']);
        }

        if ($fundData['formula_products'] ?? false) {
            $fund->updateFormulaProducts($fundData['formula_products']);
        }

        $fund->criteria()->create([
            'value' => 2,
            'operator' => '>=',
            'show_attachment' => false,
            'record_type_key' => 'children_nth',
        ]);

        $fund->fund_formulas()->create([
            'type' => 'fixed',
            'amount' => 300,
        ]);

        return $fund->refresh();
    }

    /**
     * @param Organization $organization
     * @param array $implementationData
     * @return Implementation
     */
    protected function makeTestImplementation(
        Organization $organization,
        array $implementationData = [],
    ): Implementation {
        return $organization->implementations()->create([
            'name' => fake()->title,
            ...$implementationData,
        ]);
    }
}