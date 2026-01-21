<?php

namespace App\Services\Forus\TestData;

use App\Events\FundProviders\FundProviderApprovedBudget;
use App\Events\FundProviders\FundProviderApprovedProducts;
use App\Events\Funds\FundBalanceLowEvent;
use App\Events\Funds\FundBalanceSuppliedEvent;
use App\Events\Funds\FundCreatedEvent;
use App\Events\Funds\FundEndedEvent;
use App\Events\Funds\FundProviderApplied;
use App\Events\Funds\FundStartedEvent;
use App\Events\Organizations\OrganizationCreated;
use App\Events\Products\ProductCreated;
use App\Events\VoucherTransactions\VoucherTransactionCreated;
use App\Helpers\Arr;
use App\Models\BusinessType;
use App\Models\Fund;
use App\Models\FundConfig;
use App\Models\FundCriteriaGroup;
use App\Models\FundCriteriaStep;
use App\Models\FundCriterion;
use App\Models\FundForm;
use App\Models\FundFormula;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\ImplementationPage;
use App\Models\Language;
use App\Models\Office;
use App\Models\Organization;
use App\Models\PersonBsnApiRecordType;
use App\Models\Prevalidation;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RecordType;
use App\Models\VoucherTransaction;
use App\Rules\BsnRule;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductQuery;
use App\Services\FileService\Models\File;
use App\Services\Forus\TestData\FakeGenerators\MarkdownBlockGenerator;
use App\Services\Forus\TestData\FakeGenerators\MarkdownPageGenerator;
use Carbon\Carbon;
use Database\Seeders\ImplementationsNotificationBrandingSeeder;
use Exception;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\Collection as NestedsetCollection;
use Throwable;

class TestData
{
    public int $emailNth = 0;

    protected string $configKey = 'custom';
    private mixed $tokenGenerator;

    private array|NestedsetCollection $productCategories;

    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create(Config::get('app.locale'));
        $this->configKey = Config::get('forus.test_data.test_data.config_key');
        $this->tokenGenerator = resolve('token_generator');
        $this->productCategories = ProductCategory::all();
    }

    /**
     * @return void
     */
    public function disableEmails(): void
    {
        Config::set('mail.disable', true);
        Config::set('queue.default', 'sync');
    }

    /**
     * @return void
     */
    public function enableEmails(): void
    {
        Config::set('mail.disable', false);
        Config::set('queue.default', Config::get('queue.default'));
    }

    /**
     * @param string|null $email
     * @param bool $print
     * @return Identity|null
     */
    public function makeIdentity(?string $email = null, bool $print = false): ?Identity
    {
        $emailPattern = $this->config('fund_request_email_pattern');
        $emailSlug = Str::kebab(substr($this->faker->text(rand(15, 30)), 0, -1));

        $email = $email ?: strtolower(sprintf(
            $emailPattern,
            $emailSlug . '-' . $this->integerToRoman($this->emailNth++),
        ));

        $identity = Identity::build(email: $email, records: [
            'primary_email' => $email,
            'given_name' => $this->faker->firstName(),
            'family_name' => $this->faker->lastName(),
        ]);

        $identity->primary_email->setVerified();
        $proxy = Identity::makeProxy('confirmation_code', $identity, 'active');

        if ($print) {
            $this->info("Base identity access token \"$proxy->access_token\"");
        }

        return $identity;
    }

    /**
     * @return Identity|null
     */
    public function makePrimaryIdentity(): ?Identity
    {
        return $this->makeIdentity($this->config('primary_email'));
    }

    /**
     * @param string $identity_address
     * @throws Throwable
     * @return array
     */
    public function makeSponsors(string $identity_address): array
    {
        $organizations = array_unique(Arr::pluck($this->config('funds'), 'organization_name'));
        $iConnectConfig = $this->getIConnectConfigs();

        $organizations = array_map(function ($implementation) use ($identity_address, $iConnectConfig) {
            return $this->makeOrganization($implementation, $identity_address, [
                'is_sponsor' => true,
                ...$iConnectConfig,
            ]);
        }, $organizations);

        foreach ($organizations as $organization) {
            $this->makeOffices($organization, 2);
            $organization->reimbursement_categories()->createMany(array_map(fn ($name) => compact('name'), [
                'Sports (activity)', 'Sports (product)', 'Culture', 'Education',
            ]));
        }

        return $organizations;
    }

    /**
     * @return void
     */
    public function makeSponsorRecordTypes(): void
    {
        foreach ($this->config('record_types', []) as $type) {
            $recordType = RecordType::firstOrCreate(Arr::only($type, 'key'), $type);

            $recordType->translateOrNew(app()->getLocale())->fill([
                'name' => Arr::get($type, 'name', Arr::get($type, 'key')),
            ])->save();

            foreach (Arr::get($type, 'options', []) as $option) {
                $recordType->record_type_options()->firstOrCreate([
                    'value' => $option[0],
                ])->translateOrNew(app()->getLocale())->fill([
                    'name' => $option[1],
                ])->save();
            }
        }

        // add record type mapping for person BSN API
        foreach ($this->config('person_bsn_api_record_types', []) as $type) {
            PersonBsnApiRecordType::firstOrCreate(Arr::only($type, [
                'person_bsn_api_field', 'record_type_key',
            ]), $type);
        }
    }

    /**
     * @param string $identityAddress
     * @param int|null $count
     * @throws Throwable
     */
    public function makeProviders(string $identityAddress, ?int $count = null): void
    {
        $count = $count ?: $this->config('providers_count');
        $countOffices = $this->config('provider_offices_count');
        $organizations = $this->makeOrganizations('Provider', $identityAddress, $count, [], $countOffices);

        foreach (array_random($organizations, ceil(count($organizations) / 2)) as $organization) {
            foreach (Fund::take(Fund::count() / 2)->get() as $fund) {
                FundProviderApplied::dispatch($fund, $fund->providers()->forceCreate([
                    'fund_id' => $fund->id,
                    'organization_id' => $organization->id,
                    'allow_budget' => random_int(0, 1) == 0,
                    'allow_products' => random_int(0, 10) == 0,
                    'state' => FundProvider::STATE_ACCEPTED,
                ]));
            }
        }

        foreach ($organizations as $organization) {
            $this->makeProducts($organization, $this->config('provider_products_count'));
        }

        foreach (Fund::get() as $fund) {
            $providers = Organization::whereHas('products')->pluck('id');

            if ($fund->provider_organizations_approved()->count() === 0) {
                do {
                    $provider = $providers->random();
                } while ($fund->providers()->where('organization_id', $provider)->exists());

                /** @var FundProvider $provider */
                $provider = $fund->providers()->firstOrCreate([
                    'organization_id' => $providers->random(),
                ]);

                FundProviderApplied::dispatch($fund, $provider->updateModel([
                    'allow_products' => true,
                    'allow_budget' => true,
                    'state' => FundProvider::STATE_ACCEPTED,
                ]));
            }
        }

        Fund::get()->each(static function (Fund $fund) {
            $fund->providers()->get()->each(static function (FundProvider $provider) {
                $products = $provider->organization->products;
                $products = $products->shuffle()->take(ceil($products->count() / 2));

                $provider->fund_provider_products()->insert($products->map(fn (Product $product) => [
                    'amount' => random_int(0, 10) < 7 ? $product->price / 2 : $product->price,
                    'product_id' => $product->id,
                    'fund_provider_id' => $provider->id,
                    'limit_total' => $product->unlimited_stock ? 1000 : $product->stock_amount,
                    'limit_per_identity' => $product->unlimited_stock ? 25 : ceil(max($product->stock_amount / 10, 1)),
                    'created_at' => now(),
                ])->toArray());
            });
        });

        foreach (Fund::get() as $fund) {
            foreach ($fund->providers as $fundProvider) {
                if ($fundProvider->allow_budget) {
                    FundProviderApprovedBudget::dispatch($fundProvider);
                }

                if ($fundProvider->allow_products) {
                    FundProviderApprovedProducts::dispatch($fundProvider);
                }
            }
        }
    }

    /**
     * @param Identity $identity
     * @throws Exception
     * @return void
     */
    public function applyFunds(Identity $identity): void
    {
        /** @var Prevalidation[] $prevalidations */
        $prevalidations = Prevalidation::query()
            ->where('state', Prevalidation::STATE_PENDING)
            ->where('identity_address', $identity->address)
            ->orderBy('fund_id', 'desc')
            ->get()
            ->groupBy('fund_id')
            ->map(fn (SupportCollection $arr) => $arr->first());

        foreach ($prevalidations as $prevalidation) {
            $prevalidation->assignToIdentity($identity);

            $voucher = $prevalidation->fund->makeVoucher(identity: $identity);
            $prevalidation->fund->makeFundFormulaProductVouchers($identity);

            /** @var Product $product */
            $productsQuery = ProductQuery::approvedForFundsAndActiveFilter(Product::query(), $prevalidation->fund->id);
            $product = $productsQuery->inRandomOrder()->first();
            $productIds = $productsQuery->inRandomOrder()->pluck('id');

            if ($product && !$product->sold_out) {
                $voucher->buyProductVoucher($product);
            }

            while ($voucher->amount_available > ($voucher->amount / 3)) {
                $product = Product::find((random_int(0, 10) > 6 && $productIds->count()) ? $productIds->random() : null);

                $transaction = $voucher->transactions()->forceCreate([
                    'voucher_id' => $voucher->id,
                    'amount' => ($product && !$product->sold_out) ? $product->price : random_int(
                        (int) $this->config('voucher_transaction_min'),
                        (int) $this->config('voucher_transaction_max')
                    ),
                    'product_id' => ($product && !$product->sold_out) ? $product->id : null,
                    'address' => $this->tokenGenerator->address(),
                    'organization_id' => $voucher->fund->provider_organizations_approved->pluck('id')->random(),
                    'created_at' => now()->subDays(random_int(0, 360)),
                    'state' => VoucherTransaction::STATE_SUCCESS,
                    'attempts' => /* It's Over */ 9000,
                ])->fresh();

                VoucherTransactionCreated::dispatch($transaction);
            }
        }
    }

    /**
     * @param string $prefix
     * @param string $identity_address
     * @param int $count
     * @param array $fields
     * @param int $offices_count
     * @throws Throwable
     * @return Organization[]
     * @return array
     */
    public function makeOrganizations(
        string $prefix,
        string $identity_address,
        int $count = 1,
        array $fields = [],
        int $offices_count = 0
    ): array {
        $fields = array_merge($fields, [
            'is_validator' => $prefix === 'Validator',
            'is_provider' => $prefix === 'Provider',
        ]);

        return array_map(fn ($nth) => $this->makeOrganization(
            sprintf('%s #%s: %s', $prefix, $nth, $this->makeName(5, 90 - strlen($prefix))),
            $identity_address,
            $fields,
            $offices_count,
        ), range(1, $count));
    }

    /**
     * @param string $name
     * @param string $identity_address
     * @param array $fields
     * @param int $offices_count
     * @throws Throwable
     * @return Organization
     */
    public function makeOrganization(
        string $name,
        string $identity_address,
        array $fields = [],
        int $offices_count = 0
    ): Organization {
        $data = [
            'kvk' => Organization::GENERIC_KVK,
            'iban' => $this->faker->iban('NL'),
            'phone' => '123456789',
            'email' => sprintf(
                $this->config('organization_email_pattern'),
                Str::kebab(substr($this->faker->text(random_int(15, 30)), 0, -1)),
            ),
            'bsn_enabled' => true,
            'phone_public' => true,
            'email_public' => true,
            'business_type_id' => BusinessType::pluck('id')->random(),
            'reservations_enabled' => true,
            ...$this->config('default.organizations', []),
            ...$this->config("organizations.$name.organization", []),
            ...$fields,
        ];

        $organization = Organization::forceCreate(array_merge($data, [
            'name' => $name,
            'identity_address' => $identity_address,
        ]));

        OrganizationCreated::dispatch($organization);

        $this->makeOffices(
            $organization,
            $this->config("organizations.$name.offices_count", $offices_count),
        );

        return $organization;
    }

    /**
     * @param Organization $organization
     * @param int $count
     * @param array $fields
     * @throws Throwable
     * @return void
     */
    public function makeOffices(Organization $organization, int $count = 1, array $fields = []): void
    {
        while ($count-- > 0) {
            $this->makeOffice($organization, $fields);
        }
    }

    /**
     * @param Organization $organization
     * @param array $fields
     * @throws Throwable
     * @return Office
     */
    public function makeOffice(
        Organization $organization,
        array $fields = []
    ): Office {
        $postCodes = [
            9700, 9701, 9702, 9703, 9704, 9711, 9712, 9713, 9714, 9715, 9716, 9717,
            9718, 9721, 9722, 9723, 9724, 9725, 9726, 9727, 9728, 9731, 9732, 9733,
            9734, 9735, 9736, 9737, 9738, 9741, 9742, 9743, 9744, 9745, 9746, 9747,
        ];

        $postCode = $postCodes[random_int(0, count($postCodes) - 1)];

        $office = Office::forceCreate(array_merge([
            'organization_id' => $organization->id,
            'address' => "Osloweg 131, $postCode BK, Groningen",
            'phone' => '0123456789',
            'postcode' => "$postCode BK",
            'postcode_number' => $postCode,
            'postcode_addition' => 'BK',
            'lon' => 6.606065989043237 + (random_int(-1000, 1000) / 10000),
            'lat' => 53.21694230132835 + (random_int(-1000, 1000) / 10000),
            'parsed' => true,
        ], $fields));

        $office->schedules()->insert(array_map(fn ($week_day) => [
            'start_time' => '08:00',
            'end_time' => '16:00',
            'week_day' => $week_day,
            'created_at' => now(),
            'office_id' => $office->id,
        ], range(0, 4)));

        return $office;
    }

    /**
     * @param Organization $organization
     * @param string $name
     * @throws Exception
     * @return Fund
     */
    public function makeFund(Organization $organization, string $name): Fund
    {
        /** @var \App\Models\Employee$validator $validator */
        $config = $this->config("funds.$name.fund", []);
        $validator = $organization->employeesOfRoleQuery('validation')->firstOrFail();
        $autoValidation = Arr::get($config, 'auto_requests_validation', false);

        $description = implode("  \n\n", array_map(function () {
            return $this->faker->text(random_int(300, 500));
        }, range(1, random_int(5, 10))));

        /** @var Fund $fund */
        $fund = $organization->funds()->create([
            'name' => $name,
            'state' => Fund::STATE_ACTIVE,
            'start_date' => Carbon::now()->format('Y-m-d'),
            'end_date' => Carbon::now()->addDays(60)->format('Y-m-d'),
            'description' => $description,
            'description_short' => $this->faker->text(random_int(300, 500)),
            'notification_amount' => 10000,
            'auto_requests_validation' => $autoValidation,
            'default_validator_employee_id' => $autoValidation ? $validator->id : null,
            'criteria_editable_after_start' => false,
            'external' => false,
            ...$this->config('default.funds', []),
            ...$config,
        ]);

        $topUp = $fund->getOrCreateTopUp();
        $transaction = $topUp->transactions()->forceCreate([
            'fund_top_up_id' => $topUp->id,
            'bank_transaction_id' => 'XXXX',
            'amount' => 100000,
        ]);

        $faker = fake('nl_NL');

        for ($i = rand(5, 10); $i > 0; $i--) {
            $fund->faq()->create([
                'title' => ucfirst($faker->words(rand(6, 8), true) . '?'),
                'description' => $faker->text(rand(500, 1000)),
            ]);
        }

        FundCreatedEvent::dispatch($fund);
        FundBalanceLowEvent::dispatch($fund);
        FundBalanceSuppliedEvent::dispatch($fund, $transaction);

        if ($fund->isActive()) {
            FundStartedEvent::dispatch($fund);
            FundEndedEvent::dispatch($fund);
            FundStartedEvent::dispatch($fund);
        }

        return $fund;
    }

    /**
     * @param string $name
     * @param Organization|null $organization
     * @return Implementation|Model
     */
    public function makeImplementation(
        string $name,
        ?Organization $organization = null,
    ): Implementation|Model {
        $key = str_slug($name);
        $faker = fake('nl_NL');
        $generator = new MarkdownPageGenerator($faker);
        $blockGenerator = new MarkdownBlockGenerator($faker);

        $urlData = $this->makeImplementationUrlData($key);
        $samlData = $this->makeImplementationSamlData();
        $cgiCertData = $this->makeImplementationCgiCertData();
        $configData = $this->config("implementations.$name.implementation", []);

        $languages = $configData['languages'] ?? [];
        $languages = Language::whereIn('locale', $languages)->pluck('id')->toArray();

        unset($configData['languages']);

        $implementation = Implementation::forceCreate([
            'key' => $key,
            'name' => $name,
            'organization_id' => $organization?->id,
            'informal_communication' => false,
            'allow_per_fund_notification_templates' => false,
            'overlay_enabled' => true,
            'overlay_opacity' => 10,
            'show_privacy_checkbox' => true,
            'pre_check_enabled' => true,
            'pre_check_title' => ucfirst($faker->text(rand(40, 80))),
            'pre_check_description' => $faker->text(rand(400, 600)),
            'pre_check_banner_title' => ucfirst($faker->text(rand(40, 80))),
            'pre_check_banner_label' => trim($faker->text(rand(10, 15)), '.'),
            'pre_check_banner_state' => 'public',
            'pre_check_banner_description' => $faker->text(rand(400, 600)),
            ...$this->config('default.implementations', []),
            ...$urlData,
            ...$samlData,
            ...$cgiCertData,
            ...$configData,
        ]);

        $implementation->pages()->create([
            'state' => ImplementationPage::STATE_PUBLIC,
            'page_type' => ImplementationPage::TYPE_FOOTER_CONTACT_DETAILS,
            'description' => $blockGenerator->generateContactDetails(),
        ]);

        $implementation->pages()->create([
            'state' => ImplementationPage::STATE_PUBLIC,
            'page_type' => ImplementationPage::TYPE_FOOTER_OPENING_TIMES,
            'description' => $blockGenerator->generateOpeningTimes(),
        ]);

        $pages = [
            ImplementationPage::TYPE_HOME => 500,
            // ImplementationPage::TYPE_PRODUCTS => 200,
            // ImplementationPage::TYPE_PROVIDERS => 200,
            ImplementationPage::TYPE_FUNDS => 200,
            ImplementationPage::TYPE_EXPLANATION => 4000,
            ImplementationPage::TYPE_PROVIDER => 4000,
            ImplementationPage::TYPE_PRIVACY => 4000,
            ImplementationPage::TYPE_ACCESSIBILITY => 4000,
            ImplementationPage::TYPE_TERMS_AND_CONDITIONS => 4000,
        ];

        $pageModels = [];

        foreach ($pages as $type => $length) {
            $pageModels[] = $implementation->pages()->create([
                'state' => ImplementationPage::STATE_PUBLIC,
                'page_type' => $type,
                'description' => $length > 1000 ? $generator->generate($length) : $faker->text(rand($length / 2, $length)),
                'description_alignment' => $type == ImplementationPage::TYPE_HOME ? 'center' : 'left',
            ]);
        }

        foreach ($pageModels as $pageModel) {
            $type = (array) collect(ImplementationPage::PAGE_TYPES)->firstWhere('key', $pageModel->page_type);

            if (Arr::get($type, 'faq', false)) {
                for ($i = rand(5, 10); $i > 0; $i--) {
                    $pageModel->faq()->create([
                        'title' => ucfirst($faker->words(rand(6, 8), true) . '?'),
                        'description' => $faker->text(rand(500, 1000)),
                    ]);
                }
            }
        }

        foreach ($pageModels as $pageModel) {
            $type = (array) collect(ImplementationPage::PAGE_TYPES)->firstWhere('key', $pageModel->page_type);

            if (Arr::get($type, 'blocks', false)) {
                for ($i = 3; $i > 0; $i--) {
                    $pageModel->blocks()->create([
                        'title' => ucfirst($faker->text(rand(40, 80))),
                        'description' => $faker->text(rand(400, 600)),
                        'label' => $faker->text(rand(10, 20)),
                        'button_link' => $faker->safeEmailDomain(),
                        'button_text' => $faker->text(rand(10, 20)),
                        'button_enabled' => true,
                        'button_link_label' => trim($faker->text(rand(8, 16)), '.'),
                        'button_target_blank' => true,
                    ]);
                }
            }
        }

        $implementation->languages()->sync($languages);

        return $implementation;
    }

    /**
     * @param Fund $fund
     * @return FundConfig
     */
    public function makeFundConfig(Fund $fund): FundConfig
    {
        $implementationName = $this->config("funds.$fund->name.implementation_name");
        $implementation = Implementation::where('name', $implementationName)->first();
        $implementation = $implementation ?: $this->makeImplementation($implementationName, $fund->organization);

        $config = $this->config("funds.$fund->name.fund_config", []);
        $emailRequired = Arr::get($config, 'email_required', true);

        $backofficeConfig = $fund->organization->backoffice_available ? $this->getBackofficeConfigs() : [];

        $defaultData = [
            'fund_id' => $fund->id,
            'implementation_id' => $implementation->id,
            'key' => str_slug($fund->name . '_' . date('Y')),
            'bunq_sandbox' => true,
            'csv_primary_key' => 'uid',
            'is_configured' => true,
            'allow_physical_cards' => false,
            'allow_reimbursements' => false,
            'allow_direct_payments' => false,
            'allow_generator_direct_payments' => false,
            'allow_voucher_top_ups' => false,
            'allow_voucher_records' => false,
            'email_required' => $emailRequired,
            'contact_info_enabled' => $emailRequired,
            'contact_info_required' => $emailRequired,
            'bunq_key' => $this->config('bunq_key'),
        ];

        /** @var FundConfig $fundConfig */
        $data = array_merge($defaultData, $backofficeConfig, $config);
        $fundConfig = $fund->fund_config()->forceCreate($data);

        $this->makeFundCriteriaAndFormula($fund);
        $this->makeFundForm($fund);

        return $fundConfig;
    }

    /**
     * @param Fund $fund
     * @return void
     */
    public function makeFundCriteriaAndFormula(Fund $fund): void
    {
        $configFormula = $this->config("funds.$fund->name.fund_formula");
        $configCriteria = $this->config("funds.$fund->name.fund_criteria");
        $configFundPresets = $this->config("funds.$fund->name.fund_amount_presets", []);
        $configLimitMultiplier = $this->config("funds.$fund->name.fund_limit_multiplier");

        $eligibility_key = sprintf('%s_eligible', $fund->load('fund_config')->fund_config->key);
        $criteria = [];

        $recordType = RecordType::firstOrCreate([
            'key' => $eligibility_key,
            'type' => RecordType::TYPE_BOOL,
            'control_type' => RecordType::CONTROL_TYPE_CHECKBOX,
        ], [
            'name' => "$fund->name eligible",
            'system' => false,
            'criteria' => true,
            'vouchers' => true,
            'organization_id' => $fund->organization_id,
        ]);

        if (!$fund->isAutoValidatingRequests()) {
            $criteria = array_merge($criteria, $this->config('fund_criteria'));
        } else {
            $criteria[] = [
                'record_type_key' => $recordType->key,
                'organization_id' => $fund->organization_id,
                'operator' => '=',
                'value' => 'Ja',
                'show_attachment' => false,
            ];
        }

        $limitMultiplier = $configLimitMultiplier ?: [[
            'record_type_key' => 'children_nth',
            'multiplier' => 1,
            'fund_id' => $fund->id,
        ]];

        $fundFormula = $configFormula ?: [[
            'type' => FundFormula::TYPE_FIXED,
            'amount' => $this->config('voucher_amount'),
            'fund_id' => $fund->id,
        ]];

        foreach (($configCriteria ?: $criteria) as $criterion) {
            $stepTitle = Arr::get($criterion, 'step', Arr::get($criterion, 'step.title'));
            $stepFields = is_array(Arr::get($criterion, 'step')) ? Arr::get($criterion, 'step') : [];

            /** @var FundCriteriaStep $stepModel */
            $stepModel = $stepTitle ?
                ($fund->criteria_steps()->firstWhere([
                    'title' => $stepTitle,
                    ...$stepFields,
                ]) ?: $fund->criteria_steps()->forceCreate([
                    'title' => $stepTitle,
                    ...$stepFields,
                ])) : null;

            $groupTitle = Arr::get($criterion, 'group', Arr::get($criterion, 'group.title'));
            $groupFields = is_array(Arr::get($criterion, 'group')) ? Arr::get($criterion, 'group') : [];

            /** @var FundCriteriaGroup $groupModel */
            $groupModel = $groupTitle ?
                ($fund->criteria_groups()->firstWhere([
                    'title' => $groupTitle,
                    ...$groupFields,
                ]) ?: $fund->criteria_groups()->forceCreate([
                    'title' => $groupTitle,
                    ...$groupFields,
                ])) : null;

            /** @var FundCriterion $criterionModel */
            $criterionModel = $fund->criteria()->create([
                ...array_except($criterion, ['rules', 'step']),
                'fund_criteria_step_id' => $stepModel?->id,
                'fund_criteria_group_id' => $groupModel?->id,
            ]);

            foreach ($criterion['rules'] ?? [] as $rule) {
                $criterionModel->fund_criterion_rules()->forceCreate($rule);
            }
        }

        $fund->fund_formulas()->createMany($fundFormula);
        $fund->fund_limit_multipliers()->createMany($limitMultiplier);
        $fund->syncAmountPresets($configFundPresets);
    }

    /**
     * @param Fund $fund
     * @return void
     */
    public function makeFundForm(Fund $fund): void
    {
        $fund->fund_form()->firstOrCreate([
            ...FundForm::doesntExist() ? ['id' => pow(2, 16) * 2] : [],
            'name' => $fund->name,
            'created_at' => $fund->start_date,
        ]);
    }

    /**
     * @return array
     */
    public function getBackofficeConfigs(): array
    {
        $url = $this->config('backoffice_url');
        $key = $this->config('backoffice_server_key');
        $cert = $this->config('backoffice_server_cert');

        return $url && $key && $cert ? array_merge([
            'backoffice_enabled' => true,
            'backoffice_check_partner' => true,
            'backoffice_url' => $url,
            'backoffice_key' => $key,
            'backoffice_certificate' => $cert,
        ], $this->configOnly([
            'backoffice_enabled', 'backoffice_fallback', 'backoffice_client_cert', 'backoffice_client_cert_key',
        ])) : [];
    }

    /**
     * @return array
     */
    public function getIConnectConfigs(): array
    {
        return [
            'iconnect_api_oin' => $this->config('iconnect_oin'),
            'iconnect_base_url' => $this->config('iconnect_url'),
            'iconnect_target_binding' => $this->config('iconnect_binding'),
            'iconnect_cert' => $this->config('iconnect_cert'),
            'iconnect_cert_pass' => $this->config('iconnect_cert_pass'),
            'iconnect_key' => $this->config('iconnect_key'),
            'iconnect_key_pass' => $this->config('iconnect_key_pass'),
            'iconnect_cert_trust' => $this->config('iconnect_cert_trust'),
        ];
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param array $records
     */
    public function makePrevalidations(
        Identity $identity,
        Fund $fund,
        array $records = []
    ): void {
        $recordTypes = RecordType::pluck('id', 'key');

        collect($records)->map(static function ($record) use ($recordTypes) {
            $record = collect($record);

            return $record->map(static function ($value, $key) use ($recordTypes) {
                $record_type_id = $recordTypes[$key] ?? null;

                if (!$record_type_id || $key === 'primary_email') {
                    return false;
                }

                return compact('record_type_id', 'value');
            })->filter()->toArray();
        })->filter()->map(function ($records) use ($fund, $identity) {
            $employee = $fund->organization->findEmployee($identity);

            $prevalidation = Prevalidation::forceCreate([
                'uid' => Prevalidation::makeNewUid(),
                'state' => 'pending',
                'fund_id' => $fund->id,
                'employee_id' => $employee->id,
                'organization_id' => $employee->organization_id,
                'identity_address' => $employee->identity_address,
                'validated_at' => now(),
            ]);

            $prevalidation->prevalidation_records()->createMany($records);

            return $prevalidation->updateHashes();
        });
    }

    /**
     * @param Fund $fund
     * @param int $count
     * @param array $records
     * @throws Throwable
     * @return array
     */
    public function generatePrevalidationData(
        Fund $fund,
        int $count = 10,
        array $records = []
    ): array {
        $out = [];
        $csvPrimaryKey = $fund->fund_config->csv_primary_key;

        while ($count-- > 0) {
            do {
                $primaryKeyValue = random_int(111111, 999999);
            } while (in_array($primaryKeyValue, Arr::pluck($out, $csvPrimaryKey)));

            $prevalidation = array_merge($records, [
                'gender' => 'vrouwelijk',
                'net_worth' => random_int(3, 6) * 100,
                'children_nth' => random_int(3, 5),
                'municipality' => Arr::get(RecordType::findByKey('municipality')->getOptions()[0] ?? [], 'value'),
                'birth_date' => now()->subYears(20)->format('d-m-Y'),
                'email' => $this->faker->email(),
                'iban' => $this->faker->iban('NL'),
                'civil_status' => 'Ja',
                'single_parent' => 'Ja',
                $fund->fund_config->key . '_eligible' => 'Ja',
            ]);

            $out[] = array_merge([
                ...array_only($prevalidation, $fund->criteria->pluck('record_type_key')->toArray()),
                $csvPrimaryKey => $primaryKeyValue,
            ]);
        }

        return $out;
    }

    /**
     * @param Organization $provider
     * @param int $count
     * @param array $data
     * @throws Throwable
     * @return array
     */
    public function makeProducts(Organization $provider, int $count = 10, array $data = []): array
    {
        return array_map(fn () => $this->makeProduct($provider, $data), range(1, $count));
    }

    /**
     * @param Organization $organization
     * @param array $fields
     * @throws Throwable
     * @return Product
     */
    public function makeProduct(Organization $organization, array $fields = []): Product
    {
        do {
            $name = '#' . random_int(100000, 999999) . ' ' . $this->makeName(10, 140);
        } while (Product::query()->where('name', $name)->count() > 0);

        $price = random_int(1, 20);
        $unlimited_stock = $fields['unlimited_stock'] ?? random_int(1, 10) < 3;
        $total_amount = $unlimited_stock ? 0 : random_int(1, 10) * 10;
        $sold_out = false;
        $expire_at = Carbon::now()->addDays(random_int(20, 60));
        $product_category_id = $this->productCategories->pluck('id')->random();
        $description = implode(' ', [
            'Ut aliquet nisi felis ipsum consectetuer a vulputate.',
            'Integer montes nulla in montes venenatis.',
        ]);

        $price = random_int(1, 100) >= 25 ? $price : 0;
        $price = $fields['price'] ?? $price;
        $price_type = $price > 0 ? 'regular' : 'free';
        $price_discount = 0;

        if ($price_type === 'free' && (random_int(1, 100) <= 50)) {
            $price_type = (random_int(1, 100) <= 50) ? 'discount_percentage' : 'discount_fixed';
            $price_discount = random_int(1, 9) * 10;
        }

        $startDate = format_date_locale(now()->subWeek());
        $endDate = format_date_locale(now()->addMonth());

        $product = Product::forceCreate(array_merge(compact(
            'name',
            'price',
            'total_amount',
            'sold_out',
            'expire_at',
            'product_category_id',
            'description',
            'unlimited_stock',
            'price_type',
            'price_discount'
        ), [
            'organization_id' => $organization->id,
            'info_duration' => "This offer is valid from $startDate, to $endDate",
            'info_when' => "$endDate, at 14:00",
            'info_where' => $this->faker->address(),
            'info_more_info' => 'Only for children aged 1 and up',
            'info_attention' => 'Children up to 12 years old must bring swimwear. Voucher code available once per day.',
        ], array_only($fields, [
            'name', 'total_amount', 'sold_out', 'expire_at',
        ])));

        ProductCreated::dispatch($product);

        return $product;
    }

    /**
     * @return string
     */
    public static function randomFakeBsn(): string
    {
        static $generated = [];
        static $rule = new BsnRule();

        do {
            try {
                $bsn = (string) random_int(10_000_000, 999_999_999);
            } catch (Throwable) {
                continue;
            }
        } while (!$rule->passes('bsn', $bsn) || in_array($bsn, $generated, true));

        return $generated[] = $bsn;
    }

    /**
     * Make fund requests.
     * @throws Throwable
     * @return void
     */
    public function makeFundRequests(): void
    {
        $fundRequestCount = $this->config('fund_requests_count');
        $fundRequestFilesCount = $this->config('fund_requests_files_count');

        $requesters = array_map(fn () => $this->makeIdentity(), range(1, $fundRequestCount));

        $funds = Fund::query()
            ->whereRelation('fund_config', 'allow_fund_requests', '=', true)
            ->where('auto_requests_validation', false)
            ->get();

        foreach ($funds as $fund) {
            foreach ($requesters as $requester) {
                $records = $fund->criteria->map(fn (FundCriterion $criterion) => [
                    'value' => match($criterion->operator) {
                        '=' => $criterion->value,
                        '>',
                        '>=' => match ($criterion->record_type_key) {
                            'birth_date' => $this->shiftFundCriteriaDate($criterion->value, 1),
                            default => (int) $criterion->value * 2,
                        },
                        '<',
                        '<=' => match ($criterion->record_type_key) {
                            'birth_date' => $this->shiftFundCriteriaDate($criterion->value, -1),
                            default => (int) ((int) $criterion->value / 2),
                        },
                        '*' => match ($criterion->record_type_key) {
                            'iban' => 'NL50RABO3741207772',
                            'iban_name' => 'John Doe',
                            default => '',
                        },
                        default => '',
                    },
                    'files' => array_map(
                        fn () => $this->makeFundRequestFile()->uid,
                        range(1, $fundRequestFilesCount),
                    ),
                    'record_type_key' => $criterion->record_type_key,
                    'fund_criterion_id' => $criterion->id,
                ])->toArray();

                $fund->makeFundRequest($requester, $records);
            }
        }
    }

    /**
     * @param int $integer
     * @return string
     */
    public function integerToRoman(int $integer): string
    {
        $result = '';
        $lookup = [
            'M' => 1000,
            'CM' => 900,
            'D' => 500,
            'CD' => 400,
            'C' => 100,
            'XC' => 90,
            'L' => 50,
            'XL' => 40,
            'X' => 10,
            'IX' => 9,
            'V' => 5,
            'IV' => 4,
            'I' => 1,
        ];

        foreach ($lookup as $roman => $value) {
            $result .= str_repeat($roman, (int) ($integer / $value));
            $integer %= $value;
        }

        return $result;
    }

    /**
     * @return mixed
     */
    public function getConfigData(): array
    {
        $default = $this->getConfigGroup('default');
        $custom = $this->getConfigGroup($this->getConfigKey());

        if (Arr::get($custom, 'overwrite', false)) {
            return array_merge($default, $custom);
        }

        return array_replace_recursive($default, $custom);
    }

    /**
     * @return mixed
     */
    public function getConfigGroup(string $key): array
    {
        return array_filter(array_merge(Config::get("forus.test_data.configs.$key.config", []), [
            'funds' => Config::get("forus.test_data.configs.$key.funds"),
            'record_types' => Config::get("forus.test_data.configs.$key.record_types"),
            'organizations' => Config::get("forus.test_data.configs.$key.organizations"),
            'implementations' => Config::get("forus.test_data.configs.$key.implementations"),
        ]), fn ($item) => !is_null($item));
    }

    /**
     * @param $key
     * @param null $default
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    public function config($key, $default = null): mixed
    {
        return Arr::get($this->getConfigData(), $key, $default);
    }

    /**
     * @param $keys
     * @param null $default
     * @return array
     */
    public function configOnly($keys, $default = null): array
    {
        return array_merge(array_fill_keys($keys, $default), Arr::only($this->getConfigData(), $keys));
    }

    /**
     * @return void
     */
    public function separator(): void
    {
        if (!App::runningUnitTests()) {
            echo str_repeat('-', 80) . "\n";
        }
    }

    /**
     * @param string $msg
     * @param bool $timestamp
     */
    public function info(string $msg, bool $timestamp = true): void
    {
        if (!App::runningUnitTests()) {
            echo ($timestamp ? $this->timestamp() : null) . "\e[0;34m$msg\e[0m\n";
        }
    }

    /**
     * @param string $msg
     * @param bool $timestamp
     */
    public function success(string $msg, bool $timestamp = true): void
    {
        if (!App::runningUnitTests()) {
            echo ($timestamp ? $this->timestamp() : null) . "\e[0;32m$msg\e[0m\n";
        }
    }

    /**
     * @param string $msg
     * @param bool $timestamp
     */
    public function error(string $msg, bool $timestamp = true): void
    {
        if (!App::runningUnitTests()) {
            echo ($timestamp ? $this->timestamp() : null) . "\e[0;31m$msg\e[0m\n";
        }
    }

    /**
     * @return string
     */
    public function timestamp(): string
    {
        return now()->format('[H:i:s] - ');
    }

    /**
     * @param Organization[] $sponsors
     * @throws Throwable
     * @return void
     */
    public function makeSponsorsFunds(array $sponsors): void
    {
        foreach ($sponsors as $sponsor) {
            $this->makeSponsorFunds($sponsor);
        }
    }

    /**
     * @throws Throwable
     */
    public function appendPhysicalCards(): void
    {
        $funds = Fund::whereHas('fund_config', function (Builder $builder) {
            $builder->where('allow_physical_cards', true);
        })->get();

        $nth = 1;

        $organizations = Organization::query()
            ->whereIn('id', $funds->pluck('organization_id')->toArray())
            ->whereDoesntHave('physical_card_types')
            ->get();

        foreach ($organizations as $organization) {
            $physicalCardType = $organization->physical_card_types()->create([
                'name' => 'Physical card type ' . $this->integerToRoman($nth++),
                'description' => $this->faker->paragraph(),
                'code_prefix' => '100',
                'code_blocks' => 4,
                'code_block_size' => 4,
            ]);

            $fundConfigs = FundConfig::query()
                ->whereRelation('fund', 'organization_id', $organization->id)
                ->where('allow_physical_cards', true)
                ->get();

            foreach ($fundConfigs as $fundConfig) {
                $fundConfig->fund->physical_card_types()->attach($physicalCardType->id, [
                    'allow_physical_card_linking' => true,
                    'allow_physical_card_deactivation' => true,
                ]);

                if ($fundConfig->fund_request_physical_card_enable) {
                    $fundConfig->forceFill([
                        'fund_request_physical_card_type_id' => $physicalCardType->id,
                    ])->save();
                }
            }
        }

        $funds->load('organization.physical_card_types');

        foreach ($funds as $fund) {
            foreach ($fund->vouchers->filter(fn ($voucher) => $voucher->isBudgetType()) as $voucher) {
                $type = $fund->organization->physical_card_types[0];
                $typeSize = $type->code_blocks * $type->code_block_size - strlen($type->code_prefix);

                $voucher->addPhysicalCard(
                    (string) random_int(
                        $type->code_prefix . str_repeat('1', $typeSize),
                        $type->code_prefix . str_repeat('9', $typeSize),
                    ),
                    $fund->organization->physical_card_types[0],
                );
            }
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function makeVouchers(): void
    {
        $funds = Fund::where(fn (Builder $q) => FundQuery::whereIsInternalConfiguredAndActive($q))->get();
        $vouchersPerFund = $this->config('vouchers_per_fund_count');

        foreach ($funds as $fund) {
            for ($i = 1; $i <= $vouchersPerFund; ++$i) {
                $identity = $this->makeIdentity();
                $note = 'Test data seeder!';

                $fund->makeVoucher(identity: $identity, voucherFields: compact('note'));
                $fund->makeFundFormulaProductVouchers($identity, compact('note'));
            }
        }
    }

    /**
     * @param Identity $identity
     * @throws Throwable
     * @return void
     */
    public function makeReservations(Identity $identity): void
    {
        $funds = FundQuery::whereIsInternalConfiguredAndActive(Fund::query())->get();

        foreach ($funds as $fund) {
            if (!($this->config('funds')[$fund->name]['test_reservations'] ?? true)) {
                continue;
            }

            $voucher = $fund->makeVoucher($identity, amount: 200);

            while ($voucher->amount_available > ($voucher->amount / 2)) {
                $product = ProductQuery::approvedForFundsFilter(Product::query(), $fund->id)
                    ->where('price', '>', 0)
                    ->inRandomOrder()
                    ->first();

                if (!$product) {
                    continue;
                }

                $voucher->reserveProduct(product: $product, extraData: [
                    'first_name' => $this->faker->firstName(),
                    'last_name' => $this->faker->lastName(),
                    'user_note' => $this->faker->text(random_int(64, 256)),
                ]);
            }
        }
    }

    /**
     * @param string $configKey
     * @noinspection PhpUnused
     */
    public function setConfigKey(string $configKey): void
    {
        $this->configKey = $configKey;
    }

    /**
     * @return string
     * @noinspection PhpUnused
     */
    public function getConfigKey(): string
    {
        return $this->configKey;
    }

    /**
     * @param string $key
     * @return array
     */
    protected function makeImplementationUrlData(string $key): array
    {
        return [
            'url_webshop' => str_var_replace($this->config('url_webshop'), compact('key')),
            'url_sponsor' => str_var_replace($this->config('url_sponsor'), compact('key')),
            'url_provider' => str_var_replace($this->config('url_provider'), compact('key')),
            'url_validator' => str_var_replace($this->config('url_validator'), compact('key')),
            'url_app' => str_var_replace($this->config('url_app'), compact('key')),
        ];
    }

    /**
     * @return array
     */
    protected function makeImplementationSamlData(): array
    {
        return [
            'digid_enabled' => $this->config('digid_enabled'),
            'digid_required' => true,
            'digid_sign_up_allowed' => true,
            'digid_app_id' => $this->config('digid_app_id'),
            'digid_shared_secret' => $this->config('digid_shared_secret'),
            'digid_a_select_server' => $this->config('digid_a_select_server'),
            'digid_trusted_cert' => $this->config('digid_trusted_cert'),
            'digid_connection_type' => 'cgi',
        ];
    }

    /**
     * @return array
     */
    protected function makeImplementationCgiCertData(): array
    {
        return [
            'digid_cgi_tls_key' => $this->config('digid_cgi_tls_key'),
            'digid_cgi_tls_cert' => $this->config('digid_cgi_tls_cert'),
        ];
    }

    /**
     * @param int $minLength
     * @param int $maxLength
     * @throws Exception
     * @return string
     */
    protected function makeName(int $minLength = 75, int $maxLength = 150): string
    {
        return $this->faker->text(random_int($minLength, $maxLength));
    }

    /**
     * @return File
     */
    protected function makeFundRequestFile(): File
    {
        return resolve('file')->uploadSingle(
            UploadedFile::fake()->image(Str::random() . '.jpg', 50, 50),
            'fund_request_record_proof',
        );
    }

    /**
     * @param string $date
     * @param int $days
     * @return string
     */
    private function shiftFundCriteriaDate(string $date, int $days): string
    {
        try {
            return Carbon::createFromFormat('d-m-Y', $date)->addDays($days)->format('d-m-Y');
        } catch (Exception) {
            return $date;
        }
    }

    /**
     * @param Organization $sponsor
     * @throws Throwable
     * @return void
     */
    private function makeSponsorFunds(Organization $sponsor): void
    {
        $funds = Arr::where($this->config('funds'), function ($fund) use ($sponsor) {
            return $fund['organization_name'] == $sponsor->name;
        });

        $fundTag = collect(['Tag I', 'Tag II', 'Tag III'])->random();

        foreach (array_keys($funds) as $fundName) {
            $fund = $this->makeFund($sponsor, $fundName);
            $this->makeFundConfig($fund);

            $tag = $fund->tags()->firstOrCreate([
                'key' => Str::slug($fundTag),
                'scope' => 'provider',
            ]);

            $tag->translateOrNew(app()->getLocale())->fill([
                'name' => $fundTag,
            ])->save();

            // Make prevalidations
            if ($fund->fund_config->allow_prevalidations) {
                $validator = $fund->organization->identity;

                $records = (array) $fund->criteria->reduce(function (array $list, FundCriterion $criterion) {
                    return array_merge($list, [
                        $criterion->record_type_key => match($criterion->operator) {
                            '=' => intval($criterion->value),
                            '>' => intval($criterion->value) + 1,
                            '<' => intval($criterion->value) - 1,
                            default => '',
                        },
                    ]);
                }, []);

                $this->makePrevalidations($validator, $fund, $this->generatePrevalidationData($fund, 10, $records));
            }
        }

        (new ImplementationsNotificationBrandingSeeder())->run();
    }
}
