<?php

namespace Browser;

use App\Models\Fund;
use App\Models\FundRequest;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\PersonBsnApiRecordType;
use App\Models\RecordType;
use App\Services\DigIdService\Models\DigIdSession;
use App\Services\IConnectApiService\IConnect;
use Facebook\WebDriver\Exception\TimeoutException;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundRequestPersonPrefillTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use HasFrontendActions;
    use MakesTestFundRequests;
    use MakesTestOrganizations;
    use RollbackModelsTrait;

    /**
     * @throws Throwable
     */
    public function testWebshopFundRequestPersonPrefills(): void
    {
        // record keys, will be used in iconnect mapping and key naming for criteria
        // 'age' record type will be editable to assert rules (so excluded from iconnect list)
        $iconnectRecordKeys = [
            'name',
            'salary_if_age_30',
            'salary_if_age_45',
            'salary_if_age_50',
        ];

        $recordKeys = [
            'name',
            'age',
            'salary_if_age_30',
            'salary_if_age_45',
            'salary_if_age_50',
        ];

        $mapKeys = array_combine($recordKeys, array_map(fn () => token_generator()->generate(16), $recordKeys));

        // base criteria configs
        $criteriaConfigs = [[
            'record_type' => 'string',
            'record_key' => $mapKeys['name'],
            'step' => 'Step #1',
            'control_type' => 'text',
            'operator' => '*',
            'value' => 'any',
        ], [
            'record_type' => 'number',
            'record_key' => $mapKeys['age'],
            'step' => 'Step #1',
            'control_type' => 'number',
            'operator' => '>',
            'value' => 18,
            'rules' => [[
                'record_type_key' => $mapKeys['name'],
                'operator' => '=',
                'value' => 'John Doe',
            ]],
        ], [
            'record_type' => 'number',
            'record_key' => $mapKeys['salary_if_age_30'],
            'step' => 'Step #1',
            'control_type' => 'number',
            'operator' => '<',
            'value' => 1000,
            'rules' => [[
                'record_type_key' => $mapKeys['age'],
                'operator' => '=',
                'value' => 30,
            ]],
        ], [
            'record_type' => 'number',
            'record_key' => $mapKeys['salary_if_age_45'],
            'step' => 'Step #1',
            'control_type' => 'number',
            'operator' => '<',
            'value' => 1000,
            'rules' => [[
                'record_type_key' => $mapKeys['age'],
                'operator' => '=',
                'value' => 45,
            ]],
        ], [
            'record_type' => 'number',
            'record_key' => $mapKeys['salary_if_age_50'],
            'step' => 'Step #1',
            'control_type' => 'number',
            'operator' => '<',
            'value' => 1000,
            'rules' => [[
                'record_type_key' => $mapKeys['age'],
                'operator' => '=',
                'value' => 50,
            ]],
        ]];

        // array of assertions where we assert filled values from iconnect and visibility of fields
        // also make some actions to switch between criterion fields (depends on rules)
        $assertions = [[
            'step' => 'Step #1',
            'record_key' => $mapKeys['name'],
            'control_type' => 'text',
            'assert_filled' => 'John Doe',
        ], [
            'step' => 'Step #1',
            'record_key' => $mapKeys['age'],
            'control_type' => 'number',
            'assert_filled' => '',
            'assert_hidden' => [
                $mapKeys['salary_if_age_30'],
                $mapKeys['salary_if_age_45'],
                $mapKeys['salary_if_age_50'],
            ],
            'actions' => [[
                'value' => 30,
                'record_key' => $mapKeys['salary_if_age_30'],
                'control_type' => 'number',
                'assert_filled' => 700,
                'assert_hidden' => [
                    $mapKeys['salary_if_age_45'],
                    $mapKeys['salary_if_age_50'],
                ],
            ], [
                'value' => 45,
                'record_key' => $mapKeys['salary_if_age_45'],
                'control_type' => 'number',
                'assert_filled' => 600,
                'assert_hidden' => [
                    $mapKeys['salary_if_age_30'],
                    $mapKeys['salary_if_age_50'],
                ],
            ], [
                'value' => 50,
                'record_key' => $mapKeys['salary_if_age_50'],
                'control_type' => 'number',
                'assert_filled' => 500,
                'assert_hidden' => [
                    $mapKeys['salary_if_age_30'],
                    $mapKeys['salary_if_age_45'],
                ],
            ]],
        ]];

        $start = now();
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $fund = $this->prepareFund($organization, $criteriaConfigs);

        $this->rollbackModels([
            [$implementation, $implementation->only(['digid_enabled', 'digid_required'])],
            [
                $organization,
                $organization->only([
                    'fund_request_resolve_policy', 'bsn_enabled', 'iconnect_api_oin',
                    'iconnect_target_binding', 'iconnect_base_url', 'iconnect_env', 'iconnect_key',
                    'iconnect_key_pass', 'iconnect_cert', 'iconnect_cert_pass', 'iconnect_cert_trust',
                ]),
            ],
        ], function () use (
            $implementation,
            $organization,
            $fund,
            $assertions,
            $recordKeys,
            $mapKeys,
            $iconnectRecordKeys,
        ) {
            $organization->forceFill([
                'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
            ])->save();

            $this->prepareIConnect($implementation, $iconnectRecordKeys, $mapKeys);
            $assertions = $this->mapAssertionsWithCriterionIds($fund, $assertions);

            $this->processFundRequestTestCase($implementation, $fund, $assertions);
        }, function () use ($fund, $start) {
            PersonBsnApiRecordType::truncate();
            $fund && $this->deleteFund($fund);
            RecordType::where('created_at', '>=', $start)->delete();
        });
    }

    /**
     * @param Organization $organization
     * @param array $criteriaArr
     * @return Fund
     */
    protected function prepareFund(Organization $organization, array $criteriaArr): Fund
    {
        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'bsn_confirmation_time' => 900,
            'bsn_confirmation_api_time' => 900,
            'allow_fund_requests' => true,
            'allow_prevalidations' => false,
            'allow_fund_request_prefill' => true,
        ]);

        $criteria = [];
        $recordTypes = [];

        array_walk($criteriaArr, function ($config) use ($organization, &$criteria, &$recordTypes) {
            $recordType = RecordType::create([
                'key' => $config['record_key'],
                'type' => $config['record_type'],
                'criteria' => true,
                'control_type' => $config['control_type'],
                'organization_id' => $organization->id,
            ]);

            $recordTypes[] = $recordType;

            $criteria[] = [
                'title' => "Choose item $recordType->key",
                'description' => "Choose item $recordType->key description",
                'step' => $config['step'],
                'value' => $config['value'],
                'rules' => $config['rules'] ?? [],
                'operator' => $config['operator'],
                'assert_control' => $recordType->control_type,
                'record_type_key' => $recordType->key,
                'show_attachment' => false,
            ];
        });

        $this->makeFundCriteria($fund, $criteria);

        return $fund;
    }

    /**
     * @param Implementation $implementation
     * @param array $recordKeys
     * @param array $mapKeys
     * @return void
     */
    protected function prepareIConnect(
        Implementation $implementation,
        array $recordKeys,
        array $mapKeys
    ): void {
        $implementation->forceFill([
            'digid_enabled' => true,
            'digid_required' => true,
            'digid_connection_type' => DigIdSession::CONNECTION_TYPE_CGI,
            'digid_app_id' => 'test',
            'digid_shared_secret' => 'test',
            'digid_a_select_server' => 'test',
        ])->save();

        $implementation->organization->forceFill([
            'bsn_enabled' => true,
            'iconnect_base_url' => IConnect::URL_SANDBOX,
            'iconnect_api_oin' => '0000000',
            'iconnect_target_binding' => 'BurgerlijkeStand',
            'iconnect_env' => 'sandbox',
            'iconnect_key' => "-----BEGIN CERTIFICATE-----\r\n-----END CERTIFICATE-----",
            'iconnect_key_pass' => '1111',
            'iconnect_cert' => "-----BEGIN CERTIFICATE-----\r\n-----END CERTIFICATE-----",
            'iconnect_cert_pass' => '',
            'iconnect_cert_trust' => "-----BEGIN CERTIFICATE-----\r\n-----END CERTIFICATE-----",
        ])->save();

        $personRecordTypes = array_reduce($recordKeys, fn (array $list, string $key) => [
            ...$list,
            ['person_bsn_api_field' => $key, 'record_type_key' => $mapKeys[$key]],
        ], []);

        PersonBsnApiRecordType::truncate();
        PersonBsnApiRecordType::query()->insert($personRecordTypes);
    }

    /**
     * @param Fund $fund
     * @param array $assertions
     * @return array|array[]
     */
    protected function mapAssertionsWithCriterionIds(Fund $fund, array $assertions): array
    {
        $criteriaIds = $fund->criteria()->get()->keyBy('record_type_key');

        return array_map(function ($assertion) use ($criteriaIds) {
            return [
                ...$assertion,
                'title' => $criteriaIds[$assertion['record_key']]->title,
                'description' => $criteriaIds[$assertion['record_key']]->description,
                'criterion_id' => $criteriaIds[$assertion['record_key']]->id,
                'assert_hidden' => array_map(fn ($hidden) => $criteriaIds[$hidden]->id, $assertion['assert_hidden'] ?? []),
                'actions' => array_map(fn ($action) => [
                    ...$action,
                    'criterion_id' => $criteriaIds[$action['record_key']]->id,
                    'assert_hidden' => array_map(fn ($hidden) => $criteriaIds[$hidden]->id, $action['assert_hidden'] ?? []),
                ], $assertion['actions'] ?? []),
            ];
        }, $assertions);
    }

    /**
     * @param Implementation $implementation
     * @param Fund $fund
     * @param array $criteria
     * @throws Throwable
     * @return void
     */
    protected function processFundRequestTestCase(
        Implementation $implementation,
        Fund $fund,
        array $criteria,
    ): void {
        $requester = $this->makeIdentity($this->makeUniqueEmail());

        $this->browse(function (Browser $browser) use (
            $implementation,
            $requester,
            $criteria,
            $fund,
        ) {
            $browser->visit($implementation->urlWebshop());

            $this->loginIdentity($browser, $requester);
            $browser->waitFor('@headerTitle');

            // visit fund page and assert request button available
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id"));
            $browser->waitFor('@fundTitle');
            $browser->assertSeeIn('@fundTitle', $fund->name);

            $requester->setBsnRecord('12345678');

            // assert request button available
            $browser->waitFor('@requestButton')->click('@requestButton');

            // select the DigID option and ensure the fund request form loads
            $browser->waitFor('@digidOption')->click('@digidOption');
            $browser->waitFor('@fundRequestForm');

            // assert steps overview
            $browser
                ->waitFor('@criteriaStepsOverview')
                ->within('@criteriaStepsOverview', function (Browser $browser) use ($criteria) {
                    array_walk($criteria, function ($criterion) use ($browser) {
                        $title = $criterion['step'] ?: $criterion['title'];
                        $browser->assertSee($title);
                    });
                });

            $browser->waitFor('@nextStepButton')->click('@nextStepButton');

            $this->fillRequestForm($browser, $criteria);

            // Logout user
            $this->logout($browser);
        });

        $request = FundRequest::where('fund_id', $fund->id)
            ->where('identity_id', $requester->id)
            ->exists();

        $this->assertTrue($request);
    }

    /**
     * @param Browser $browser
     * @param array $criteria
     * @throws TimeoutException
     * @return void
     */
    protected function fillRequestForm(Browser $browser, array $criteria): void
    {
        $browser->waitFor('@fundRequestForm');

        $browser->within('@fundRequestForm', function (Browser $browser) use ($criteria) {
            // group criteria by steps
            $criteria = collect($criteria);
            $singleCriteria = $criteria->whereNull('step')->map(fn ($criterion) => [$criterion]);
            $criteriaSteps = $criteria->whereNotNull('step')->groupBy('step');
            $data = $criteriaSteps->merge($singleCriteria);

            foreach ($data as $step) {
                foreach ($step as $field) {
                    // assert filled value
                    $elementId = '#criterion_' . $field['criterion_id'];
                    $this->assertInputValue($browser, $elementId, $field['control_type'], $field['assert_filled']);

                    // assert hidden controls is missing
                    foreach ($field['assert_hidden'] ?? [] as $item) {
                        $browser->assertMissing('#criterion_' . $item);
                    }

                    // actions - fill input and assert criterion, depends on rules, has right filled value
                    foreach ($field['actions'] ?? [] as $item) {
                        $this->clearInputCustom($browser, $elementId);
                        $this->fillInput($browser, $elementId, $item['control_type'], $item['value']);

                        // assert filled value
                        $this->assertInputValue(
                            $browser,
                            '#criterion_' . $item['criterion_id'],
                            $item['control_type'],
                            $item['assert_filled']
                        );

                        // assert hidden controls is missing
                        foreach ($item['assert_hidden'] ?? [] as $hiddenItem) {
                            $browser->assertMissing('#criterion_' . $hiddenItem);
                        }
                    }
                }

                $browser->click('@nextStepButton');
            }

            // need pause before submit form - sometimes on frontend it doesn't work immediately after steps
            $browser->pause(100);

            // submit fund request form
            $browser->waitFor('@submitButton')->click('@submitButton');
            $browser->waitFor('@fundRequestSuccess');
        });
    }

    /**
     * @param Browser $browser
     * @param string $selector
     * @param string $control
     * @param string|int|null $value
     * @throws TimeoutException
     * @return void
     */
    protected function assertInputValue(Browser $browser, string $selector, string $control, string|int|null $value): void
    {
        switch ($control) {
            case 'date':
            case 'step':
            case 'select':
                $browser->waitFor($selector);
                $browser->waitForTextIn($selector, $value);
                break;
            case 'number':
            case 'currency':
            case 'text':
                $browser->waitFor($selector);
                $browser->assertValue($selector, $value);
                break;
        }
    }
}
