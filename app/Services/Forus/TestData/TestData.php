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
use App\Models\FundCriterion;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Office;
use App\Models\Organization;
use App\Models\Prevalidation;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\RecordType;
use App\Models\Tag;
use App\Models\VoucherTransaction;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductQuery;
use App\Services\FileService\Models\File;
use Carbon\Carbon;
use Database\Seeders\ImplementationsNotificationBrandingSeeder;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Kalnoy\Nestedset\Collection as NestedsetCollection;

class TestData
{
    private mixed $tokenGenerator;

    private array|NestedsetCollection $productCategories;

    public int $emailNth = 0;

    protected string $configKey = 'custom';

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
            $emailPattern, $emailSlug . '-' . $this->integerToRoman($this->emailNth++),
        ));

        $identity = Identity::make($email, [
            'primary_email' => $email,
            'given_name' => $this->faker->firstName,
            'family_name' => $this->faker->lastName,
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
     * @return array
     * @throws \Throwable
     */
    public function makeSponsors(string $identity_address): array
    {
        $organizations = array_unique(Arr::pluck($this->config('funds'), 'organization_name'));

        $organizations = array_map(function($implementation) use ($identity_address) {
            return $this->makeOrganization($implementation, $identity_address, [
                'is_sponsor' => true,
            ]);
        }, $organizations);

        foreach ($organizations as $organization) {
            $this->makeOffices($organization, 2);
            $organization->reimbursement_categories()->createMany(array_map(fn($name) => compact('name'), [
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

            foreach (Arr::get($type, 'options', []) as $option) {
                $recordType->record_type_options()->updateOrCreate([
                    'value' => $option[0],
                ], [
                    'name' => $option[1],
                ]);
            }
        }
    }

    /**
     * @param string $identityAddress
     * @param int|null $count
     * @throws \Throwable
     */
    public function makeProviders(string $identityAddress, ?int $count = null): void
    {
        $count = $count ?: $this->config('providers_count');
        $countOffices = $this->config('provider_offices_count');
        $organizations = $this->makeOrganizations("Provider", $identityAddress, $count, [], $countOffices);

        foreach (array_random($organizations, ceil(count($organizations) / 2)) as $organization) {
            foreach (Fund::take(Fund::count() / 2)->get() as $fund) {
                FundProviderApplied::dispatch($fund, $fund->providers()->forceCreate([
                    'fund_id'           => $fund->id,
                    'organization_id'   => $organization->id,
                    'allow_budget'      => $fund->isTypeBudget() && random_int(0, 1) == 0,
                    'allow_products'    => $fund->isTypeBudget() && random_int(0, 10) == 0,
                    'state'             => FundProvider::STATE_ACCEPTED,
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
                    'allow_products'    => $fund->isTypeBudget(),
                    'allow_budget'      => $fund->isTypeBudget(),
                    'state'             => FundProvider::STATE_ACCEPTED,
                ]));
            }
        }

        Fund::get()->each(static function(Fund $fund) {
            $fund->providers()->get()->each(static function(FundProvider $provider) {
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
     * @param string $identity_address
     * @param int|null $count
     * @throws \Throwable
     */
    public function makeExternalValidators(string $identity_address, ?int $count = null): void
    {
        $count = $count ?: $this->config('validators_count');
        $organizations = $this->makeOrganizations("Validator", $identity_address, $count);

        foreach ($organizations as $key => $organization) {
            $this->makeOffices($organization, random_int(1, 2));

            $organization->update([
                'is_validator' => true,
                'validator_auto_accept_funds' => $key <= ($this->config('validators_count') / 2),
            ]);
        }
    }

    /**
     * @param Identity $identity
     * @return void
     * @throws \Exception
     */
    public function applyFunds(Identity $identity): void
    {
        $prevalidations = Prevalidation::where([
            'state' => 'pending',
            'identity_address' => $identity->address
        ])->get()->groupBy('fund_id')->map(static function(SupportCollection $arr) {
            return $arr->first();
        });

        foreach ($prevalidations as $prevalidation) {
            foreach($prevalidation->prevalidation_records as $record) {
                if ($record->record_type->key === 'bsn') {
                    continue;
                }

                $identity
                    ->makeRecord($record->record_type, $record->value)
                    ->makeValidationRequest()
                    ->approve($prevalidation->identity);
            }

            $prevalidation->update([
                'state' => 'used'
            ]);

            $voucher = $prevalidation->fund->makeVoucher($identity->address);
            $prevalidation->fund->makeFundFormulaProductVouchers($identity->address);

            /** @var Product $product */
            $productsQuery = ProductQuery::approvedForFundsAndActiveFilter(Product::query(), $prevalidation->fund->id);
            $product = $productsQuery->inRandomOrder()->first();
            $productIds = $productsQuery->inRandomOrder()->pluck('id');

            if ($product && !$product->sold_out) {
                $voucher->buyProductVoucher($product);
            }

            while ($voucher->fund->isTypeBudget() && $voucher->amount_available > ($voucher->amount / 3)) {
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
     * @return Organization[]
     * @param int $offices_count
     * @return array
     * @throws \Throwable
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
     * @return Organization
     * @throws \Throwable
     */
    public function makeOrganization(
        string $name,
        string $identity_address,
        array $fields = [],
        int $offices_count = 0
    ): Organization {
        $data = [
            'kvk' => Organization::GENERIC_KVK,
            'iban' => $this->config('default_organization_iban') ?: $this->faker->iban('NL'),
            'phone' => '123456789',
            'email' => sprintf(
                $this->config('organization_email_pattern'),
                Str::kebab(substr($this->faker->text(random_int(15, 30)), 0, -1)),
            ),
            'bsn_enabled' => true,
            'phone_public' => true,
            'email_public' => true,
            'business_type_id' => BusinessType::pluck('id')->random(),
            'reservations_budget_enabled' => true,
            'reservations_subsidy_enabled' => true,
            ...$this->config("default.organizations", []),
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
     * @return void
     * @throws \Throwable
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
     * @return Office
     * @throws \Throwable
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
            'organization_id'   => $organization->id,
            'address'           => "Osloweg 131, $postCode BK, Groningen",
            'phone'             => '0123456789',
            'postcode'          => "$postCode BK",
            'postcode_number'   => $postCode,
            'postcode_addition' => 'BK',
            'lon'               => 6.606065989043237 + (random_int(-1000, 1000) / 10000),
            'lat'               => 53.21694230132835 + (random_int(-1000, 1000) / 10000),
            'parsed'            => true
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
     * @return Fund
     */
    public function makeFund(Organization $organization, string $name): Fund
    {
        /** @var \App\Models\Employee$validator $validator */
        $config = $this->config("funds.$name.fund", []);
        $validator = $organization->employeesOfRoleQuery('validation')->firstOrFail();
        $autoValidation = Arr::get($config, 'auto_requests_validation', false);

        /** @var Fund $fund */
        $fund = $organization->funds()->create([
            'name'                          => $name,
            'start_date'                    => Carbon::now()->format('Y-m-d'),
            'end_date'                      => Carbon::now()->addDays(60)->format('Y-m-d'),
            'state'                         => Fund::STATE_ACTIVE,
            'description'                   => $this->faker->text(rand(600, 6500)),
            'notification_amount'           => 10000,
            'auto_requests_validation'      => $autoValidation,
            'default_validator_employee_id' => $autoValidation ? $validator->id : null,
            'criteria_editable_after_start' => false,
            'type'                          => Fund::TYPE_BUDGET,
            ...$this->config("default.funds", []),
            ...$config,
        ]);

        $topUp = $fund->getOrCreateTopUp();
        $transaction = $topUp->transactions()->forceCreate([
            'fund_top_up_id' => $topUp->id,
            'bank_transaction_id' => "XXXX",
            'amount' => 100000,
        ]);

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

        $urlData = $this->makeImplementationUrlData($key);
        $samlData = $this->makeImplementationSamlData();
        $cgiCertData = $this->makeImplementationCgiCertData();
        $configData = $this->config("implementations.$name.implementation", []);

        return Implementation::forceCreate([
            'key' => $key,
            'name' => $name,
            'organization_id' => $organization?->id,
            'informal_communication' => false,
            'allow_per_fund_notification_templates' => false,
            'productboard_api_key' => $this->config('productboard_api_key'),
            ...$this->config("default.implementations", []),
            ...$urlData,
            ...$samlData,
            ...$cgiCertData,
            ...$configData,
        ]);
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
            "digid_cgi_tls_key" => $this->config('digid_cgi_tls_key'),
            "digid_cgi_tls_cert" => $this->config('digid_cgi_tls_cert'),
        ];
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
        $hashBsn = Arr::get($config, "hash_bsn", false);
        $emailRequired = Arr::get($config, "email_required", true);

        $backofficeConfig = $fund->organization->backoffice_available ? $this->getBackofficeConfigs() : [];
        $iConnectConfig = $this->getIConnectConfigs();

        $defaultData = [
            'fund_id'                   => $fund->id,
            'implementation_id'         => $implementation->id,
            'key'                       => str_slug($fund->name . '_' . date('Y')),
            'bunq_sandbox'              => true,
            'csv_primary_key'           => 'uid',
            'is_configured'             => true,
            'allow_physical_cards'      => false,
            'allow_reimbursements'      => false,
            'allow_direct_payments'     => false,
            'allow_generator_direct_payments' => false,
            'allow_voucher_top_ups'     => false,
            'allow_voucher_records'     => false,
            'email_required'            => $emailRequired,
            'contact_info_enabled'      => $emailRequired,
            'contact_info_required'     => $emailRequired,
            'hash_bsn'                  => $hashBsn,
            'hash_bsn_salt'             => $hashBsn ? $fund->name : null,
            'bunq_key'                  => $this->config('bunq_key'),
        ];

        /** @var FundConfig $fundConfig */
        $data = array_merge($defaultData, $iConnectConfig, $backofficeConfig, $config);
        $fundConfig = $fund->fund_config()->forceCreate($data);

        $this->makeFundCriteriaAndFormula($fund);

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
        $configLimitMultiplier = $this->config("funds.$fund->name.fund_limit_multiplier");

        $eligibility_key = sprintf("%s_eligible", $fund->load('fund_config')->fund_config->key);
        $criteria = [];

        $recordType = RecordType::firstOrCreate([
            'key' => $eligibility_key,
            'type' => 'bool',
        ], [
            'name' => "$fund->name eligible",
            'system' => false,
            'criteria' => true,
            'vouchers' => true,
            'organization_id' => $fund->organization_id,
        ]);

        if (!$fund->isAutoValidatingRequests()) {
            $criteria = array_merge($criteria, $this->config('funds_criteria'));
        } else {
            $criteria[] = [
                'record_type_key' => $recordType->key,
                'organization_id' => $fund->organization_id,
                'operator' => '=',
                'value' => 'Ja',
                'show_attachment' => false,
            ];
        }

        $limitMultiplier = !$configLimitMultiplier && $fund->isTypeSubsidy() ? [[
            'record_type_key' => 'children_nth',
            'multiplier' => 1,
            'fund_id' => $fund->id,
        ]] : ($configLimitMultiplier ?: []);

        $fundFormula = $configFormula ?: [[
            'type' => 'fixed',
            'amount' => $fund->isTypeBudget() ? $this->config('voucher_amount'): 0,
            'fund_id' => $fund->id,
        ]];

        $fund->criteria()->createMany($configCriteria ?: $criteria);
        $fund->fund_formulas()->createMany($fundFormula);
        $fund->fund_limit_multipliers()->createMany($limitMultiplier);
    }

    /**
     * @return array
     */
    public function getBackofficeConfigs (): array
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
            'backoffice_fallback', 'backoffice_client_cert', 'backoffice_client_cert_key',
        ])): [];
    }

    /**
     * @return array
     */
    public function getIConnectConfigs(): array {
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

        collect($records)->map(static function($record) use ($recordTypes) {
            $record = collect($record);

            return $record->map(static function($value, $key) use ($recordTypes) {
                $record_type_id = $recordTypes[$key] ?? null;

                if (!$record_type_id || $key === 'primary_email') {
                    return false;
                }

                return compact('record_type_id', 'value');
            })->filter()->toArray();
        })->filter()->map(function($records) use ($fund, $identity) {
            $prevalidation = Prevalidation::forceCreate([
                'uid' => Prevalidation::makeNewUid(),
                'state' => 'pending',
                'fund_id' => $fund->id,
                'organization_id' => $fund->organization_id,
                'identity_address' => $identity->address,
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
     * @return array
     * @throws \Throwable
     */
    public function generatePrevalidationData(
        Fund $fund,
        int $count = 10,
        array $records = []
    ): array {
        $out = [];
        // second prevalidation in list
        $bsn_prevalidation_index = $count - 2;

        // third prevalidation in list is partner for second prevalidation
        $bsn_prevalidation_partner_index = $count - 3;

        $csvPrimaryKey = $fund->fund_config->csv_primary_key;
        $envLoremBsn = $this->config('prevalidation_bsn', false);

        while ($count-- > 0) {
            do {
                $primaryKeyValue = random_int(111111, 999999);
            } while (collect($out)->pluck($csvPrimaryKey)->search($primaryKeyValue) !== false);

            $bsnValue = $envLoremBsn && ($count === $bsn_prevalidation_index) ?
                $envLoremBsn : self::randomFakeBsn();

            $bsnValuePartner = $envLoremBsn && ($count === $bsn_prevalidation_partner_index) ?
                $envLoremBsn : self::randomFakeBsn();

            $prevalidation = array_merge($records, [
                'gender' => 'Female',
                'net_worth' => random_int(3, 6) * 100,
                'children_nth' => random_int(3, 5),
                'municipality' => Arr::get(RecordType::findByKey('municipality')->getOptions()[0] ?? [], 'value'),
                'birth_date' => now()->subYears(20)->format('d-m-Y'),
                'email' => $this->faker->email(),
                'iban' => $this->faker->iban('NL'),
                'civil_status' => 'true',
                'single_parent' => 'true',
            ], $fund->fund_config->hash_bsn ? [
                'bsn_hash' => $fund->getHashedValue($bsnValue),
                'partner_bsn_hash' => $fund->getHashedValue($bsnValuePartner),
            ] : []);

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
     * @return array
     * @throws \Throwable
     */
    public function makeProducts(Organization $provider, int $count = 10, array $data = []): array
    {
        return array_map(fn () => $this->makeProduct($provider, $data), range(1, $count));
    }

    /**
     * @param Organization $organization
     * @param array $fields
     * @return Product
     * @throws \Throwable
     */
    public function makeProduct(Organization $organization, array $fields = []): Product
    {
        do {
            $name = '#' . random_int(100000, 999999) . " " . $this->makeName(10, 140);
        } while(Product::query()->where('name', $name)->count() > 0);

        $price = random_int(1, 20);
        $unlimited_stock = $fields['unlimited_stock'] ?? random_int(1, 10) < 3;
        $total_amount = $unlimited_stock ? 0 : random_int(1, 10) * 10;
        $sold_out = false;
        $expire_at = Carbon::now()->addDays(random_int(20, 60));
        $product_category_id = $this->productCategories->pluck('id')->random();
        $description = implode(' ', [
            "Ut aliquet nisi felis ipsum consectetuer a vulputate.",
            "Integer montes nulla in montes venenatis."
        ]);

        $price = random_int(1, 100) >= 25 ? $price : 0;
        $price = $fields['price'] ?? $price;
        $price_type = $price > 0 ? 'regular' : 'free';
        $price_discount = 0;

        if ($price_type === 'free' && (random_int(1, 100) <= 50)) {
            $price_type = (random_int(1, 100) <= 50) ? 'discount_percentage' : 'discount_fixed';
            $price_discount = random_int(1, 9) * 10;
        }

        $product = Product::forceCreate(array_merge(compact(
            'name', 'price', 'total_amount', 'sold_out',
            'expire_at', 'product_category_id', 'description', 'unlimited_stock',
            'price_type', 'price_discount'
        ), [
            'organization_id' => $organization->id,
        ], array_only($fields, [
            'name', 'total_amount', 'sold_out', 'expire_at'
        ])));

        ProductCreated::dispatch($product);

        return $product;
    }

    /**
     * @param int $minLength
     * @param int $maxLength
     * @return string
     * @throws \Exception
     */
    protected function makeName(int $minLength = 75, int $maxLength = 150): string
    {
        return $this->faker->text(random_int($minLength, $maxLength));
    }

    /**
     * @return int
     * @throws \Throwable
     */
    public static function randomFakeBsn(): int
    {
        static $randomBsn = [];

        do {
            try {
                $bsn = random_int(100000000, 900000000);
            } catch (\Throwable) {
                $bsn = false;
            }
        } while ($bsn && in_array($bsn, $randomBsn, true));

        return $randomBsn[] = $bsn;
    }

    /**
     * Make fund requests
     * @return void
     * @throws \Throwable
     */
    public function makeFundRequests(): void
    {
        $fundRequestCount = $this->config('fund_requests_count');
        $fundRequestFilesCount = $this->config('fund_requests_files_count');

        $requesters = array_map(fn() => $this->makeIdentity(), range(1, $fundRequestCount));

        $funds = Fund::query()
            ->whereRelation('fund_config', 'allow_fund_requests', '=', true)
            ->where('auto_requests_validation', false)
            ->get();

        foreach ($funds as $fund) {
            foreach ($requesters as $requester) {
                $records = $fund->criteria->map(fn (FundCriterion $criterion) => [
                    'value' => match($criterion->operator) {
                        '=' => $criterion->value,
                        '>' => (int) $criterion->value * 2,
                        '<' => (int) ((int) $criterion->value / 2),
                        default => '',
                    },
                    'files' => array_map(
                        fn() => $this->makeFundRequestFile()->uid,
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
     * @param int $integer
     * @return string
     */
    public function integerToRoman(int $integer): string {
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
            'I' => 1
        ];

        foreach ($lookup as $roman => $value){
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
        echo str_repeat('-', 80) . "\n";
    }

    /**
     * @param string $msg
     * @param bool $timestamp
     */
    public function info(string $msg, bool $timestamp = true): void
    {
        echo ($timestamp ? $this->timestamp() : null) . "\e[0;34m$msg\e[0m\n";
    }

    /**
     * @param string $msg
     * @param bool $timestamp
     */
    public function success(string $msg, bool $timestamp = true): void
    {
        echo ($timestamp ? $this->timestamp() : null) . "\e[0;32m$msg\e[0m\n";
    }

    /**
     * @param string $msg
     * @param bool $timestamp
     */
    public function error(string $msg, bool $timestamp = true): void
    {
        echo ($timestamp ? $this->timestamp() : null) . "\e[0;31m$msg\e[0m\n";
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
     * @return void
     * @throws \Throwable
     */
    public function makeSponsorsFunds(array $sponsors): void
    {
        foreach ($sponsors as $sponsor) {
            $this->makeSponsorFunds($sponsor);
        }
    }

    /**
     * @param Organization $sponsor
     * @return void
     * @throws \Throwable
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

            $fund->tags()->save(Tag::firstOrCreate([
                'key' => Str::slug($fundTag),
                'name' => $fundTag,
                'scope' => 'provider',
            ]));

            // Make prevalidations
            if ($fund->fund_config->allow_prevalidations) {
                $validator = $fund->organization->identity;

                $records = $fund->criteria->reduce(function (array $list, FundCriterion $criterion) {
                    return array_merge($list, [
                        $criterion->record_type_key => match($criterion->operator) {
                            '=' => intval($criterion->value),
                            '>' => intval($criterion->value) + 1,
                            '<' => intval($criterion->value) - 1,
                            default => '',
                        }
                    ]);
                }, []);

                $this->makePrevalidations($validator, $fund, $this->generatePrevalidationData($fund, 10, $records));
            }
        }

        (new ImplementationsNotificationBrandingSeeder)->run();
    }

    /**
     * @throws \Throwable
     */
    public function appendPhysicalCards(): void
    {
        $funds = Fund::whereHas('fund_config', function(Builder $builder) {
            $builder->where('allow_physical_cards', true);
        })->get();

        foreach ($funds as $fund) {
            foreach ($fund->vouchers as $voucher) {
                $voucher->addPhysicalCard((string) random_int(11111111, 99999999));
            }
        }
    }

    /**
     * @return void
     * @throws \Throwable
     */
    public function makeVouchers(): void
    {
        $funds = Fund::where(fn(Builder $q) => FundQuery::whereActiveFilter($q))->get();
        $vouchersPerFund = $this->config('vouchers_per_fund_count');

        foreach ($funds as $fund) {
            for ($i = 1; $i <= $vouchersPerFund; ++$i) {
                $identity_address = $this->makeIdentity();
                $note = 'Test data seeder!';

                $fund->makeVoucher($identity_address, compact('note'));
                $fund->makeFundFormulaProductVouchers($identity_address, compact('note'));
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
}