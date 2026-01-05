<?php

namespace Tests\Browser;

use App\Models\Fund;
use App\Models\FundCriterion;
use App\Models\FundFormula;
use App\Models\FundRequest;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\PersonBsnApiRecordType;
use App\Models\RecordType;
use App\Models\Role;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesTestFundRequestPrefills;
use Tests\Traits\MakesTestFundRequests;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class FundRequestPrefillsTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFundRequestPrefills;
    use MakesTestFundRequests;
    use MakesTestFunds;
    use MakesTestOrganizations;
    use HasFrontendActions;
    use RollbackModelsTrait;

    /**
     * @throws Throwable
     */
    public function testWebshopFundRequestPrefillsHappyPath(): void
    {
        // configure implementation and organization for prefills
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $this->assertNotNull($implementation);
        $this->assertNotNull($organization);

        $implementationData = $implementation->only(['digid_enabled', 'digid_required']);
        $organizationData = $organization->only([
            'fund_request_resolve_policy', 'bsn_enabled', 'iconnect_env', 'iconnect_key', 'iconnect_key_pass',
            'iconnect_cert', 'iconnect_cert_pass', 'iconnect_cert_trust', 'iconnect_target_binding',
            'iconnect_api_oin', 'iconnect_base_url',
        ]);

        $prefillKey = token_generator()->generate(16);
        $manualKey = token_generator()->generate(16);

        $recordTypes = collect([
            $this->makeRecordTypeForKey(
                $organization,
                $prefillKey,
                RecordType::TYPE_STRING,
                RecordType::CONTROL_TYPE_TEXT,
            ),
            $this->makeRecordTypeForKey(
                $organization,
                $manualKey,
                RecordType::TYPE_NUMBER,
                RecordType::CONTROL_TYPE_NUMBER,
            ),
            $this->makeRecordTypeForKey(
                $organization,
                Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
                RecordType::TYPE_NUMBER,
                RecordType::CONTROL_TYPE_NUMBER,
            ),
            $this->makeRecordTypeForKey(
                $organization,
                Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
                RecordType::TYPE_NUMBER,
                RecordType::CONTROL_TYPE_NUMBER,
            ),
        ])->filter(fn (RecordType $recordType) => $recordType->wasRecentlyCreated);

        $prefillRecordType = PersonBsnApiRecordType::create([
            'person_bsn_api_field' => 'naam.geslachtsnaam',
            'record_type_key' => $prefillKey,
        ]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
            'key' => 'nijmegen-vi',
        ], $implementation);

        $criteria = [[
            'step' => 'Step #1',
            'title' => 'Prefill last name',
            'value' => 'any',
            'operator' => '*',
            'optional' => true,
            'record_type_key' => $prefillKey,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'step' => 'Step #1',
            'title' => 'Partner count',
            'value' => 1,
            'operator' => '>=',
            'optional' => true,
            'record_type_key' => Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'step' => 'Step #1',
            'title' => 'Children count',
            'value' => 1,
            'operator' => '>=',
            'optional' => true,
            'record_type_key' => Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'step' => 'Step #1',
            'title' => 'Manual number',
            'value' => 1,
            'operator' => '>=',
            'optional' => false,
            'record_type_key' => $manualKey,
            'show_attachment' => false,
        ]];

        $this->rollbackModels([
            [$implementation, $implementationData],
            [$organization, $organizationData],
        ], function () use ($implementation, $organization, $fund, $criteria) {
            // configure organization policy and disable digid
            $implementation->forceFill([
                'digid_enabled' => false,
                'digid_required' => false,
            ])->save();

            // configure iConnect settings and seed criteria
            $this->enablePersonBsnApiForOrganization($organization);
            $organization->forceFill([
                'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
            ])->save();

            $this->makeFundCriteria($fund, $criteria);
            $this->processFundRequestPrefillsTestCase($implementation, $fund, [
                'bsn' => '999993112',
                'prefill_value' => 'Zon',
                'partner_value' => 'Gerrit',
                'child_value' => 'Zoey',
                'number_value' => 3,
            ]);
        }, function () use ($fund, $recordTypes, $prefillRecordType) {
            $fund && $this->deleteFund($fund);
            $prefillRecordType?->delete();
            $recordTypes->each(fn (RecordType $recordType) => $recordType->delete());
        });
    }

    /**
     * @throws Throwable
     */
    public function testWebshopFundRequestPrefillsRequiredCriteriaError(): void
    {
        // configure implementation and organization for prefills
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $this->assertNotNull($implementation);
        $this->assertNotNull($organization);

        $implementationData = $implementation->only(['digid_enabled', 'digid_required']);
        $organizationData = $organization->only([
            'fund_request_resolve_policy', 'bsn_enabled', 'iconnect_env', 'iconnect_key', 'iconnect_key_pass',
            'iconnect_cert', 'iconnect_cert_pass', 'iconnect_cert_trust', 'iconnect_target_binding',
            'iconnect_api_oin', 'iconnect_base_url',
        ]);

        $prefillKey = token_generator()->generate(16);
        $recordType = $this->makeRecordTypeForKey(
            $organization,
            $prefillKey,
            RecordType::TYPE_STRING,
            RecordType::CONTROL_TYPE_TEXT,
        );

        $prefillRecordType = PersonBsnApiRecordType::create([
            'person_bsn_api_field' => 'missing.path',
            'record_type_key' => $prefillKey,
        ]);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
        ], $implementation);

        $criteria = [[
            'step' => 'Step #1',
            'title' => 'Required prefill',
            'value' => 'any',
            'operator' => '*',
            'optional' => false,
            'record_type_key' => $prefillKey,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ]];

        $this->rollbackModels([
            [$implementation, $implementationData],
            [$organization, $organizationData],
        ], function () use ($implementation, $organization, $fund, $criteria) {
            // configure organization policy and disable digid
            $implementation->forceFill([
                'digid_enabled' => false,
                'digid_required' => false,
            ])->save();

            // configure iConnect settings and seed criteria
            $this->enablePersonBsnApiForOrganization($organization);
            $organization->forceFill([
                'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
            ])->save();

            $this->makeFundCriteria($fund, $criteria);
            $this->processFundRequestPrefillErrorTestCase($implementation, $fund, '999993112');
        }, function () use ($fund, $recordType, $prefillRecordType) {
            $fund && $this->deleteFund($fund);
            $prefillRecordType?->delete();
            $recordType?->wasRecentlyCreated && $recordType->delete();
        });
    }

    /**
     * @throws Throwable
     */
    public function testWebshopFundRequestPrefillsTakenByPartner(): void
    {
        // configure implementation and organization for prefills
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $this->assertNotNull($implementation);
        $this->assertNotNull($organization);

        $implementationData = $implementation->only(['digid_enabled', 'digid_required']);
        $organizationData = $organization->only([
            'fund_request_resolve_policy', 'bsn_enabled', 'iconnect_env', 'iconnect_key', 'iconnect_key_pass',
            'iconnect_cert', 'iconnect_cert_pass', 'iconnect_cert_trust', 'iconnect_target_binding',
            'iconnect_api_oin', 'iconnect_base_url',
        ]);

        $manualKey = token_generator()->generate(16);
        $recordTypes = collect([
            $this->makeRecordTypeForKey(
                $organization,
                Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
                RecordType::TYPE_NUMBER,
                RecordType::CONTROL_TYPE_NUMBER,
            ),
            $this->makeRecordTypeForKey(
                $organization,
                $manualKey,
                RecordType::TYPE_NUMBER,
                RecordType::CONTROL_TYPE_NUMBER,
            ),
        ])->filter(fn (RecordType $recordType) => $recordType->wasRecentlyCreated);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'allow_fund_request_prefill' => true,
            'allow_prevalidations' => false,
        ], $implementation);

        $criteria = [[
            'step' => 'Step #1',
            'title' => 'Partner count',
            'value' => 1,
            'operator' => '>=',
            'optional' => true,
            'record_type_key' => Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS,
            'show_attachment' => false,
            'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
        ], [
            'step' => 'Step #1',
            'title' => 'Manual number',
            'value' => 1,
            'operator' => '>=',
            'optional' => false,
            'record_type_key' => $manualKey,
            'show_attachment' => false,
        ]];

        $this->rollbackModels([
            [$implementation, $implementationData],
            [$organization, $organizationData],
        ], function () use ($implementation, $organization, $fund, $criteria) {
            // configure organization policy and disable digid
            $implementation->forceFill([
                'digid_enabled' => false,
                'digid_required' => false,
            ])->save();

            // configure iConnect settings and seed criteria
            $this->enablePersonBsnApiForOrganization($organization);
            $organization->forceFill([
                'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
            ])->save();

            $this->makeFundCriteria($fund, $criteria);

            // ensure partner identity used by prefill lookup has an active voucher
            $partner = Identity::findByBsn('999994542') ?: $this->makeIdentity($this->makeUniqueEmail(), '999994542');

            if (!$fund->identityHasActiveVoucher($partner)) {
                $fund->makeVoucher($partner);
            }

            $this->processFundRequestPrefillErrorTestCase($implementation, $fund, '999993112');
        }, function () use ($fund, $recordTypes) {
            $fund && $this->deleteFund($fund);
            $recordTypes->each(fn (RecordType $recordType) => $recordType->delete());
        });
    }

    /**
     * @throws Throwable
     */
    public function testWebshopFundRequestPrefillsCompleteWorkflow(): void
    {
        // configure implementation and organization for prefills
        $implementation = Implementation::byKey('nijmegen');
        $organization = $implementation->organization;

        $this->assertNotNull($implementation);
        $this->assertNotNull($organization);

        $implementationData = $implementation->only(['digid_enabled', 'digid_required']);
        $organizationData = $organization->only([
            'fund_request_resolve_policy', 'bsn_enabled', 'iconnect_env', 'iconnect_key', 'iconnect_key_pass',
            'iconnect_cert', 'iconnect_cert_pass', 'iconnect_cert_trust', 'iconnect_target_binding',
            'iconnect_api_oin', 'iconnect_base_url',
        ]);

        // create record types used by the full workflow
        $recordTypeConfigs = [
            ['key' => 'given_name', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'family_name', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'birth_date', 'type' => RecordType::TYPE_DATE, 'control_type' => RecordType::CONTROL_TYPE_DATE],
            ['key' => 'street', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'house_number', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_NUMBER],
            ['key' => 'house_number_addition', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'postal_code', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'city', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'telephone', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => Fund::RECORD_TYPE_KEY_PARTNERS_SAME_ADDRESS, 'type' => RecordType::TYPE_NUMBER, 'control_type' => RecordType::CONTROL_TYPE_NUMBER],
            ['key' => Fund::RECORD_TYPE_KEY_CHILDREN_SAME_ADDRESS, 'type' => RecordType::TYPE_NUMBER, 'control_type' => RecordType::CONTROL_TYPE_NUMBER],
            ['key' => 'income_checkbox_paid_work', 'type' => RecordType::TYPE_BOOL, 'control_type' => RecordType::CONTROL_TYPE_CHECKBOX],
            ['key' => 'income_checkbox_subsidy', 'type' => RecordType::TYPE_BOOL, 'control_type' => RecordType::CONTROL_TYPE_CHECKBOX],
            ['key' => 'income_checkbox_wia', 'type' => RecordType::TYPE_BOOL, 'control_type' => RecordType::CONTROL_TYPE_CHECKBOX],
            ['key' => 'income_checkbox_alimony', 'type' => RecordType::TYPE_BOOL, 'control_type' => RecordType::CONTROL_TYPE_CHECKBOX],
            ['key' => 'income_checkbox_own_company', 'type' => RecordType::TYPE_BOOL, 'control_type' => RecordType::CONTROL_TYPE_CHECKBOX],
            ['key' => 'income_checkbox_hobby', 'type' => RecordType::TYPE_BOOL, 'control_type' => RecordType::CONTROL_TYPE_CHECKBOX],
            ['key' => 'income_checkbox_tax_credit', 'type' => RecordType::TYPE_BOOL, 'control_type' => RecordType::CONTROL_TYPE_CHECKBOX],
            ['key' => 'income_checkbox_other', 'type' => RecordType::TYPE_BOOL, 'control_type' => RecordType::CONTROL_TYPE_CHECKBOX],
            ['key' => 'income_other', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'partner_bsn', 'type' => RecordType::TYPE_NUMBER, 'control_type' => RecordType::CONTROL_TYPE_NUMBER],
            ['key' => 'partner_first_name', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'partner_last_name', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'partner_birth_date', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'partner_gender', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'child_1_bsn', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'child_1_first_name', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'child_1_last_name', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'child_1_birth_date', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'child_1_gender', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'child_2_bsn', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'child_2_first_name', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'child_2_last_name', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'child_2_birth_date', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'child_2_gender', 'type' => RecordType::TYPE_STRING, 'control_type' => RecordType::CONTROL_TYPE_TEXT],
            ['key' => 'children_age_group_0_3', 'type' => RecordType::TYPE_NUMBER, 'control_type' => RecordType::CONTROL_TYPE_NUMBER],
            ['key' => 'children_age_group_4_11', 'type' => RecordType::TYPE_NUMBER, 'control_type' => RecordType::CONTROL_TYPE_NUMBER],
            ['key' => 'children_age_group_12_13', 'type' => RecordType::TYPE_NUMBER, 'control_type' => RecordType::CONTROL_TYPE_NUMBER],
            ['key' => 'children_age_group_14_17', 'type' => RecordType::TYPE_NUMBER, 'control_type' => RecordType::CONTROL_TYPE_NUMBER],
            ['key' => 'children_age_group_18_99', 'type' => RecordType::TYPE_NUMBER, 'control_type' => RecordType::CONTROL_TYPE_NUMBER],
        ];

        $recordTypes = collect($recordTypeConfigs)
            ->map(fn (array $config) => $this->makeRecordTypeForKey(
                $organization,
                $config['key'],
                $config['type'],
                $config['control_type'],
            ))
            ->filter(fn (RecordType $recordType) => $recordType->wasRecentlyCreated);

        // define person bsn prefill mappings
        $prefillRecordTypes = collect([
            PersonBsnApiRecordType::firstOrCreate([
                'person_bsn_api_field' => 'naam.voornamen',
                'record_type_key' => 'given_name',
            ]),
            PersonBsnApiRecordType::firstOrCreate([
                'person_bsn_api_field' => 'naam.geslachtsnaam',
                'record_type_key' => 'family_name',
            ]),
            PersonBsnApiRecordType::firstOrCreate([
                'person_bsn_api_field' => 'geboorte.datum.datum',
                'record_type_key' => 'birth_date',
            ]),
            PersonBsnApiRecordType::firstOrCreate([
                'person_bsn_api_field' => 'verblijfplaats.straat',
                'record_type_key' => 'street',
            ]),
            PersonBsnApiRecordType::firstOrCreate([
                'person_bsn_api_field' => 'verblijfplaats.huisnummer',
                'record_type_key' => 'house_number',
            ]),
            PersonBsnApiRecordType::firstOrCreate([
                'person_bsn_api_field' => 'verblijfplaats.huisnummertoevoeging',
                'record_type_key' => 'house_number_addition',
            ]),
            PersonBsnApiRecordType::firstOrCreate([
                'person_bsn_api_field' => 'verblijfplaats.postcode',
                'record_type_key' => 'postal_code',
            ]),
        ])->filter(fn (PersonBsnApiRecordType $recordType) => $recordType->wasRecentlyCreated);

        $fund = $this->makeTestFund($organization, [
            'type' => 'budget',
        ], [
            'key' => 'nijmegen-vi',
            'allow_fund_request_prefill' => true,
            'help_enabled' => true,
            'help_title' => 'Questions or need help?',
            'help_block_text' => 'Social Support Desk',
            'help_button_text' => 'Contact Us',
            'help_email' => 'example@example.com',
            'help_phone' => '+1234567890',
            'help_website' => 'www.example.com',
            'help_description' => implode(' ', [
                'For questions, please call between 9:00 AM and 1:00 PM. Ask for the Social Support Desk.',
                'Would you prefer to visit us? Our address is: Soestdijkerweg 182, 3734 MH, Wad en Heuvel',
            ]),
            'help_show_email' => true,
            'help_show_phone' => true,
            'help_show_website' => true,
            'show_subsidies' => true,
            'show_qr_limits' => true,
            'show_requester_limits' => true,
            'allow_physical_cards' => true,
            'allow_prevalidations' => false,
        ], $implementation);

        $employeeIdentity = $this->makeIdentity($this->makeUniqueEmail());
        $rolesValidator = Role::where('key', 'validation')->pluck('id')->toArray();
        $employee = $organization->addEmployee($employeeIdentity, $rolesValidator);

        $this->rollbackModels([
            [$implementation, $implementationData],
            [$organization, $organizationData],
        ], function () use ($implementation, $organization, $fund, $employee) {
            // configure organization policy and disable digid
            $implementation->forceFill([
                'digid_enabled' => false,
                'digid_required' => false,
            ])->save();

            // configure iConnect settings and auto-approve requests
            $this->enablePersonBsnApiForOrganization($organization);
            $organization->forceFill([
                'fund_request_resolve_policy' => Organization::FUND_REQUEST_POLICY_AUTO_REQUESTED,
            ])->save();

            $fund->forceFill([
                'default_validator_employee_id' => $employee->id,
                'auto_requests_validation' => false,
            ])->save();

            // replace default formulas with the configured multipliers
            $fund->fund_formulas()->delete();
            $fund->fund_formulas()->create([
                'type' => FundFormula::TYPE_MULTIPLY,
                'amount' => '100.00',
                'record_type_key' => 'partner_same_address_nth',
            ]);
            $fund->fund_formulas()->create([
                'type' => FundFormula::TYPE_MULTIPLY,
                'amount' => '50.00',
                'record_type_key' => 'children_age_group_4_11',
            ]);

            // create criteria groups for each step
            $personalGroup = $this->makeCriteriaGroup(
                $fund,
                title: 'My personal details',
            );
            $addressGroup = $this->makeCriteriaGroup(
                $fund,
                title: 'Address details',
                order: 2,
            );
            $contactGroup = $this->makeCriteriaGroup(
                $fund,
                title: 'Contact details',
                order: 3,
            );
            $incomeGroup = $this->makeCriteriaGroup(
                $fund,
                title: 'Enter how much income you and/or your partner have.',
                description: 'Vink aan welke inkomsten u en/of uw partner hebben gehad',
                required: true,
            );

            // define criteria with steps, groups, and rules
            $criteria = [[
                'title' => 'First name',
                'description' => '',
                'record_type_key' => 'given_name',
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
                'step' => 'Step 1: Personal information',
                'fund_criteria_group_id' => $personalGroup->id,
                'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
            ], [
                'title' => 'Last name',
                'description' => '',
                'record_type_key' => 'family_name',
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
                'step' => 'Step 1: Personal information',
                'fund_criteria_group_id' => $personalGroup->id,
                'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
            ], [
                'title' => 'Date of birth',
                'description' => '',
                'record_type_key' => 'birth_date',
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
                'step' => 'Step 1: Personal information',
                'fund_criteria_group_id' => $personalGroup->id,
                'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
            ], [
                'title' => 'Street name',
                'description' => '',
                'record_type_key' => 'street',
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
                'step' => 'Step 1: Personal information',
                'fund_criteria_group_id' => $addressGroup->id,
                'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
            ], [
                'title' => 'House number',
                'description' => '',
                'record_type_key' => 'house_number',
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
                'step' => 'Step 1: Personal information',
                'fund_criteria_group_id' => $addressGroup->id,
                'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
            ], [
                'title' => 'House number extension',
                'description' => '',
                'record_type_key' => 'house_number_addition',
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
                'step' => 'Step 1: Personal information',
                'fund_criteria_group_id' => $addressGroup->id,
                'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
                'optional' => true,
            ], [
                'title' => 'Postal code',
                'description' => '',
                'record_type_key' => 'postal_code',
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
                'step' => 'Step 1: Personal information',
                'fund_criteria_group_id' => $addressGroup->id,
                'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
            ], [
                'title' => 'City',
                'description' => '',
                'record_type_key' => 'city',
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
                'step' => 'Step 1: Personal information',
                'fund_criteria_group_id' => $addressGroup->id,
                'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
                'optional' => true,
            ], [
                'title' => 'Phone',
                'description' => 'What is the phone number we can reach you at?',
                'record_type_key' => 'telephone',
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
                'step' => 'Step 1: Personal information',
                'fund_criteria_group_id' => $contactGroup->id,
            ], [
                'title' => 'Partner',
                'description' => '',
                'record_type_key' => 'partner_same_address_nth',
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
                'step' => 'Step 2: Family situation',
                'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
                'optional' => true,
            ], [
                'title' => 'Children',
                'description' => '',
                'record_type_key' => 'children_same_address_nth',
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
                'step' => 'Step 2: Family situation',
                'fill_type' => FundCriterion::FILL_TYPE_PREFILL,
                'optional' => true,
            ], [
                'title' => 'Paid work (Wages)',
                'label' => 'Paid work (Wages)',
                'description' => '',
                'record_type_key' => 'income_checkbox_paid_work',
                'operator' => '*',
                'value' => 'Ja',
                'show_attachment' => true,
                'optional' => true,
                'step' => 'Step 3: Income',
                'fund_criteria_group_id' => $incomeGroup->id,
            ], [
                'title' => 'Subsidy',
                'label' => 'Subsidy',
                'description' => '',
                'record_type_key' => 'income_checkbox_subsidy',
                'operator' => '*',
                'value' => 'Ja',
                'show_attachment' => true,
                'optional' => true,
                'step' => 'Step 3: Income',
                'fund_criteria_group_id' => $incomeGroup->id,
            ], [
                'title' => 'WIA benefit',
                'label' => 'WIA benefit',
                'description' => '',
                'record_type_key' => 'income_checkbox_wia',
                'operator' => '*',
                'value' => 'Ja',
                'show_attachment' => true,
                'optional' => true,
                'step' => 'Step 3: Income',
                'fund_criteria_group_id' => $incomeGroup->id,
            ], [
                'title' => 'Alimentatie',
                'label' => 'Alimentatie',
                'description' => '',
                'record_type_key' => 'income_checkbox_alimony',
                'operator' => '*',
                'value' => 'Ja',
                'show_attachment' => true,
                'optional' => true,
                'step' => 'Step 3: Income',
                'fund_criteria_group_id' => $incomeGroup->id,
            ], [
                'title' => 'Eigen bedrijf',
                'label' => 'Eigen bedrijf',
                'description' => '',
                'record_type_key' => 'income_checkbox_own_company',
                'operator' => '*',
                'value' => 'Ja',
                'show_attachment' => true,
                'optional' => true,
                'step' => 'Step 3: Income',
                'fund_criteria_group_id' => $incomeGroup->id,
            ], [
                'title' => 'Hobby',
                'label' => 'Hobby',
                'description' => '',
                'record_type_key' => 'income_checkbox_hobby',
                'operator' => '*',
                'value' => 'Ja',
                'show_attachment' => true,
                'optional' => true,
                'step' => 'Step 3: Income',
                'fund_criteria_group_id' => $incomeGroup->id,
            ], [
                'title' => 'Heffingskorting',
                'label' => 'Heffingskorting',
                'description' => '',
                'record_type_key' => 'income_checkbox_tax_credit',
                'operator' => '*',
                'value' => 'Ja',
                'show_attachment' => true,
                'optional' => true,
                'step' => 'Step 3: Income',
                'fund_criteria_group_id' => $incomeGroup->id,
            ], [
                'title' => 'Ander inkomen, namelijk:',
                'label' => 'Ander inkomen, namelijk:',
                'description' => '',
                'record_type_key' => 'income_checkbox_other',
                'operator' => '*',
                'value' => 'Ja',
                'show_attachment' => true,
                'optional' => true,
                'step' => 'Step 3: Income',
                'fund_criteria_group_id' => $incomeGroup->id,
            ], [
                'title' => 'Ander inkomen',
                'description' => '',
                'record_type_key' => 'income_other',
                'operator' => '*',
                'value' => '',
                'show_attachment' => false,
                'optional' => false,
                'step' => 'Step 3: Income',
                'fund_criteria_group_id' => $incomeGroup->id,
                'rules' => [[
                    'record_type_key' => 'income_checkbox_other',
                    'operator' => '=',
                    'value' => 'Ja',
                ]],
            ]];

            $this->makeFundCriteria($fund, $criteria);

            $this->processFundRequestPrefillsCompleteWorkflowTestCase($implementation, $fund, [
                'bsn' => '999993112',
                'phone' => '0612345678',
                'other_income' => 'Other income details',
                'expected_amount' => 300,
            ]);
        }, function () use ($fund, $prefillRecordTypes, $recordTypes) {
            // cleanup fund, mappings, and record types
            $fund && $this->deleteFund($fund);
            $prefillRecordTypes->each(fn (PersonBsnApiRecordType $recordType) => $recordType->delete());
            $recordTypes->each(fn (RecordType $recordType) => $recordType->delete());
        });
    }

    /**
     * @param Implementation $implementation
     * @param Fund $fund
     * @param array $config
     * @throws Throwable
     * @return void
     */
    protected function processFundRequestPrefillsTestCase(
        Implementation $implementation,
        Fund $fund,
        array $config,
    ): void {
        // create a requester and run the form flow
        $requester = $this->makeIdentity($this->makeUniqueEmail(), $config['bsn']);
        $this->forgetFundPrefillCache($fund, $config['bsn']);

        $this->browse(function (Browser $browser) use ($implementation, $fund, $requester, $config) {
            $browser->visit($implementation->urlWebshop());

            $this->loginIdentity($browser, $requester);
            $browser->waitFor('@headerTitle');

            // visit fund page and open the request form
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id"));
            $browser->waitFor('@fundTitle');
            $browser->assertSeeIn('@fundTitle', $fund->name);

            $browser->waitFor('@requestButton')->click('@requestButton');
            $browser->waitFor('@criteriaStepsOverview');

            // navigate to the criteria step
            $browser->waitFor('@nextStepButton')->click('@nextStepButton');
            $browser->waitFor('@fundRequestForm');

            // assert prefill panels are visible
            // assert prefills and related panels are rendered
            $browser->assertSee($config['prefill_value']);
            $browser->assertSee($config['partner_value']);
            $browser->assertSee($config['child_value']);

            // complete manual input and continue
            // fill manual field and proceed to overview
            $this->fillInput($browser, '@controlNumber', 'number', $config['number_value']);
            $browser->click('@nextStepButton');

            $browser->waitFor('@submitButton');
            // assert prefills shown on overview
            $browser->assertSee($config['prefill_value']);
            $browser->assertSee($config['partner_value']);
            $browser->assertSee($config['child_value']);

            // submit fund request form
            $browser->click('@submitButton');
            $browser->waitFor('@fundRequestSuccess');

            // Logout user
            $this->logout($browser);
        });

        $request = FundRequest::where('fund_id', $fund->id)
            ->where('identity_id', $requester->id)
            ->exists();

        // assert fund request persisted
        $this->assertTrue($request);
    }

    /**
     * @param Implementation $implementation
     * @param Fund $fund
     * @param string $bsn
     * @throws Throwable
     * @return void
     */
    protected function processFundRequestPrefillErrorTestCase(
        Implementation $implementation,
        Fund $fund,
        string $bsn,
    ): void {
        // create a requester and run the form flow
        $requester = $this->makeIdentity($this->makeUniqueEmail(), $bsn);
        $this->forgetFundPrefillCache($fund, $bsn);

        $this->browse(function (Browser $browser) use ($implementation, $fund, $requester) {
            $browser->visit($implementation->urlWebshop());

            $this->loginIdentity($browser, $requester);
            $browser->waitFor('@headerTitle');

            // visit fund page and open the request form
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id"));
            $browser->waitFor('@fundTitle');
            $browser->assertSeeIn('@fundTitle', $fund->name);

            $browser->waitFor('@requestButton')->click('@requestButton');

            // assert error screen is shown instead of the form
            $browser->waitForText('Aanvraag mislukt');
            $browser->assertMissing('@fundRequestForm');
        });

        $request = FundRequest::where('fund_id', $fund->id)
            ->where('identity_id', $requester->id)
            ->exists();

        // assert no fund request created
        $this->assertFalse($request);
    }

    /**
     * @param Implementation $implementation
     * @param Fund $fund
     * @param array $config
     * @throws Throwable
     * @return void
     */
    protected function processFundRequestPrefillsCompleteWorkflowTestCase(
        Implementation $implementation,
        Fund $fund,
        array $config,
    ): void {
        // create a requester and run the full form flow
        $requester = $this->makeIdentity($this->makeUniqueEmail(), $config['bsn']);
        $this->forgetFundPrefillCache($fund, $config['bsn']);

        $this->browse(function (Browser $browser) use ($implementation, $fund, $requester, $config) {
            $browser->visit($implementation->urlWebshop());

            $this->loginIdentity($browser, $requester);
            $browser->waitFor('@headerTitle');

            // visit fund page and open the request form
            $browser->visit($implementation->urlWebshop("fondsen/$fund->id"));
            $browser->waitFor('@fundTitle');
            $browser->assertSeeIn('@fundTitle', $fund->name);

            $browser->waitFor('@requestButton')->click('@requestButton');
            $browser->waitFor('@criteriaStepsOverview');

            // go to step 1 and check grouped prefills
            $browser->waitFor('@nextStepButton')->click('@nextStepButton');
            $browser->waitForTextIn('.sign_up-pane-header', 'Step 1: Personal information');
            $browser->assertSee('My personal details');
            $browser->assertSee('Address details');
            $browser->assertSee('Contact details');

            // fill contact information and continue
            $this->fillInput($browser, '@controlText', 'text', $config['phone']);
            $browser->click('@nextStepButton');

            // step 2 includes partner and children prefills
            $browser->waitForTextIn('.sign_up-pane-header', 'Step 2: Family situation');
            $browser->assertSee('Gerrit');
            $browser->assertSee('Zoey');
            $browser->click('@nextStepButton');

            // step 3 includes required income group with attachments
            $browser->waitForTextIn('.sign_up-pane-header', 'Step 3: Income');
            $browser->assertSee('Enter how much income you and/or your partner have.');
            $browser->assertSee('Vink aan welke inkomsten u en/of uw partner hebben gehad');

            // select required checkbox criteria
            $checkboxes = $browser->elements('@controlCheckbox');
            $this->assertGreaterThanOrEqual(8, count($checkboxes));

            $checkboxes[0]->click();
            $checkboxes[7]->click();

            // upload required attachments for checked criteria
            $this->attachFilesToFileUploader($browser, count: 2);

            // fill conditional income field and continue
            $this->fillInput($browser, '@controlText', 'text', $config['other_income']);
            $browser->click('@nextStepButton');

            // submit fund request form
            $browser->waitFor('@submitButton');
            $browser->assertSee($config['phone']);
            $browser->assertSee($config['other_income']);
            $browser->click('@submitButton');
            $browser->waitFor('@fundRequestSuccess');

            // logout user
            $this->logout($browser);
        });

        $fundRequest = FundRequest::where('fund_id', $fund->id)
            ->where('identity_id', $requester->id)
            ->first();

        // approve fund request to generate voucher
        $this->assertNotNull($fundRequest);

        if (!$fundRequest->employee_id) {
            $employee = $fund->default_validator_employee;
            $employee && $fundRequest->assignEmployee($employee);
        }

        $fundRequest->approve();

        // assert voucher amount after approval
        $voucher = $fund->vouchers()
            ->where('identity_id', $requester->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($voucher);
        $this->assertEquals($config['expected_amount'], (float) $voucher->amount);
    }
}
