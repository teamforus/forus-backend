<?php

namespace Database\Seeders;

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
use App\Models\VoucherTransaction;
use App\Scopes\Builders\FundQuery;
use App\Scopes\Builders\ProductQuery;
use Carbon\Carbon;
use Faker\Provider\Payment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Config;
use Kalnoy\Nestedset\Collection as NestedsetCollection;

/**
 * Class LoremDbSeeder
 */
class LoremDbSeeder extends Seeder
{
    private mixed $tokenGenerator;
    private array|NestedsetCollection $productCategories;
    private mixed $primaryEmail;

    private mixed $countProviders;
    private mixed $countValidators;
    private mixed $countFundRequests;
    private mixed $fundRequestEmailPattern;
    private mixed $vouchersPerFund;
    private int $emailNth = 0;

    private array $implementations = [
        'Zuidhorn', 'Nijmegen', 'Westerkwartier', 'Stadjerspas', 'Berkelland',
        'Kerstpakket', 'Noordoostpolder', 'Oostgelre', 'Winterswijk', 'Potjeswijzer',
    ];

    private array $implementationsWithFunds = [
        'Zuidhorn', 'Nijmegen', 'Westerkwartier', 'Stadjerspas', 'Berkelland',
        'Kerstpakket', 'Noordoostpolder', 'Oostgelre', 'Winterswijk', 'Potjeswijzer',
    ];

    private array $sponsorsWithMultipleFunds = [
        'Westerkwartier' => 2,
        'Stadjerspas' => 4,
        'Nijmegen' => 2,
    ];

    private array $subsidyFunds = [
        'Stadjerspas',
    ];

    private array $implementationsWithInformalCommunication = [
        'Zuidhorn', 'Nijmegen',
    ];

    private array $implementationsWithDigidSignup = [
        'Stadjerspas', 'Nijmegen',
    ];

    private array $implementationsWithRequiredDigId = [
        'Nijmegen', 'Stadjerspas',
    ];

    private array $fundsWithCriteriaEditableAfterLaunch = [
        'Zuidhorn', 'Nijmegen',
    ];

    private array $fundsWithPhysicalCards = [
        'Nijmegen', 'Stadjerspas',
    ];

    private array $fundsWithAutoValidation = [
        'Nijmegen', 'Stadjerspas III', 'Stadjerspas IV',
    ];

    private array $fundsWithDirectPayments = [
        'Nijmegen', 'Stadjerspas',
    ];

    private array $fundsWithoutEmailRequirement = [
        'Stadjerspas IV',
    ];

    private array $sponsorsWithSponsorProducts = [
        'Stadjerspas'
    ];

    private array $sponsorsWithBackoffice = [
        'Zuidhorn', 'Nijmegen',
    ];

    private array $fundKeyOverwrite = [
        'Nijmegen' => 'meedoen',
    ];

    private array $organizationsWithCustomNotifications = [
        'Zuidhorn', 'Nijmegen',
    ];

    private array $fundsWithVoucherTopUp = [
        'Nijmegen', 'Zuidhorn',
    ];

    /**
     * LoremDbSeeder constructor.
     */
    public function __construct()
    {
        $this->tokenGenerator = resolve('token_generator');

        $this->countProviders = config('forus.seeders.lorem_db_seeder.providers_count');
        $this->countValidators = config('forus.seeders.lorem_db_seeder.validators_count');
        $this->countFundRequests = config('forus.seeders.lorem_db_seeder.fund_requests_count');

        $this->productCategories = ProductCategory::all();

        $this->primaryEmail = config('forus.seeders.lorem_db_seeder.default_email');
        $this->fundRequestEmailPattern = config('forus.seeders.lorem_db_seeder.fund_request_email_pattern');
        $this->vouchersPerFund = config('forus.seeders.lorem_db_seeder.vouchers_per_fund_count');
    }

    /**
     * @return void
     */
    private function disableEmails(): void
    {
        Config::set('mail.disable', true);
        Config::set('queue.default', 'sync');
    }

    /**
     * @return void
     */
    private function enableEmails(): void
    {
        Config::set('mail.disable', false);
        Config::set('queue.default', Config::get('queue.default'));
    }

    /**
     * Run the database seeds
     *
     * @throws \Throwable
     */
    public function run(): void
    {
        $this->disableEmails();

        $this->productCategories = ProductCategory::all();
        $this->info("⇾ Making base identity!");
        $baseIdentity = $this->makeIdentity($this->primaryEmail, true);
        $this->success("✓ Identity created!");

        $this->info("⇾ Making sponsors!");
        $sponsors = $this->makeSponsors($baseIdentity->address);
        $this->success("✓ Sponsors created!");
        $this->separator();

        $this->info("⇾ Making funds!");
        $this->makeSponsorsFunds($sponsors);
        $this->success("✓ Funds created!");
        $this->separator();

        $this->info("⇾ Making providers!");
        $this->makeProviders($baseIdentity->address, $this->countProviders);
        $this->success("✓ Providers created!");
        $this->separator();

        $this->info("⇾ Making validators!");
        $this->makeExternalValidators($baseIdentity->address, $this->countValidators);
        $this->success("✓ Validators created!");
        $this->separator();

        $this->info("⇾ Applying providers to funds!");
        $this->applyFunds($baseIdentity);
        $this->success("✓ Providers applied to funds!");
        $this->separator();

        $this->info("⇾ Making vouchers!");
        $this->makeVouchers();
        $this->success("✓ Vouchers created!");
        $this->separator();

        $this->info("⇾ Making other implementations!");
        $this->makeOtherImplementations(array_diff($this->implementations, $this->implementationsWithFunds));
        $this->success("✓ Other implementations created!");
        $this->separator();

        $this->info("⇾ Making fund requests!");
        $this->makeFundRequests();
        $this->success("✓ Fund requests created!");
        $this->separator();

        $this->info("⇾ Appending physical cards!");
        $this->appendPhysicalCards();
        $this->success("✓ Physical cards attached!");
        $this->separator();

        $this->enableEmails();
    }

    /**
     * @param string|null $primaryEmail
     * @param bool $print
     * @return Identity|null
     */
    public function makeIdentity(?string $primaryEmail = null, bool $print = false): ?Identity
    {
        $primaryEmail = $primaryEmail ?: strtolower(sprintf(
            $this->fundRequestEmailPattern,
            $this->integerToRoman($this->emailNth++)
        ));

        $identity = Identity::make($primaryEmail, [
            'primary_email' => $primaryEmail,
            'given_name' => 'John',
            'family_name' => 'Doe'
        ]);

        $identity->primary_email->setVerified();
        $proxy = Identity::makeProxy('confirmation_code', $identity, 'active');

        if ($print) {
            $this->info("Base identity access token \"$proxy->access_token\"");
        }

        return $identity;
    }

    /**
     * @param string $identity_address
     * @return array
     * @throws \Throwable
     */
    public function makeSponsors(string $identity_address): array
    {
        $organizations = array_map(function($implementation) use ($identity_address) {
            return $this->makeOrganization($implementation, $identity_address, [
                'is_sponsor' => true
            ]);
        }, $this->implementationsWithFunds);

        foreach ($organizations as $organization) {
            $this->makeOffices($organization, 2);
        }

        return $organizations;
    }

    /**
     * @param string $identityAddress
     * @param int $count
     * @throws \Throwable
     */
    public function makeProviders(string $identityAddress, int $count = 10): void
    {
        $countOffices = $this->config('provider_offices_count');
        $organizations = $this->makeOrganizations("Provider", $identityAddress, $count, [], $countOffices);

        foreach (array_random($organizations, ceil(count($organizations) / 2)) as $organization) {
            foreach (Fund::take(Fund::count() / 2)->get() as $fund) {
                FundProviderApplied::dispatch($fund, $fund->providers()->create([
                    'organization_id'   => $organization->id,
                    'allow_budget'      => $fund->isTypeBudget() && random_int(0, 2),
                    'allow_products'    => $fund->isTypeBudget() && random_int(0, 2),
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
                    'organization_id'   => $providers->random(),
                ]);

                FundProviderApplied::dispatch($fund, $provider->updateModel([
                    'allow_products'    => $fund->isTypeBudget(),
                    'allow_budget'      => $fund->isTypeBudget(),
                    'state'             => FundProvider::STATE_ACCEPTED,
                ]));
            }
        }

        Fund::whereType(Fund::TYPE_SUBSIDIES)->get()->each(static function(Fund $fund) {
            $fund->providers()->get()->each(static function(FundProvider $provider) {
                $fundProviderProducts = $provider->organization->products->random(
                    ceil($provider->organization->products->count() / 2)
                )->map(static function(Product $product) {
                    return [
                        'amount' => random_int(0, 10) < 7 ? $product->price / 2 : $product->price,
                        'product_id' => $product->id,
                        'limit_total' => $product->unlimited_stock ? 1000 : $product->stock_amount,
                        'limit_per_identity' => $product->unlimited_stock ? 25 : ceil(
                            max($product->stock_amount / 10, 1)
                        ),
                    ];
                })->toArray();

                $provider->fund_provider_products()->createMany($fundProviderProducts);
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
     * @param int $count
     * @throws \Throwable
     */
    public function makeExternalValidators(
        string $identity_address,
        int $count = 10
    ): void {
        $organizations = $this->makeOrganizations("Validator", $identity_address, $count);

        foreach ($organizations as $key => $organization) {
            $this->makeOffices($organization, random_int(1, 2));

            $organization->update([
                'is_validator' => true,
                'validator_auto_accept_funds' => $key <= ($this->countValidators / 2),
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

            if ($this->config('no_vouchers')) {
                continue;
            }

            $voucher = $prevalidation->fund->makeVoucher($identity->address);
            $prevalidation->fund->makeFundFormulaProductVouchers($identity->address);

            if ($this->config('no_product_vouchers')) {
                continue;
            }

            /** @var Product $product */
            $productsQuery = ProductQuery::approvedForFundsAndActiveFilter(Product::query(), $prevalidation->fund->id);
            $product = $productsQuery->inRandomOrder()->first();
            $productIds = $productsQuery->inRandomOrder()->pluck('id');

            if ($product && !$product->sold_out) {
                $voucher->buyProductVoucher($product);
            }

            while ($voucher->fund->isTypeBudget() && $voucher->amount_available > ($voucher->amount / 3)) {
                $product = Product::find((random_int(0, 10) > 6 && $productIds->count()) ? $productIds->random() : null);

                $transaction = $voucher->transactions()->create([
                    'amount' => ($product && !$product->sold_out) ? $product->price : random_int(
                        (int) config('forus.seeders.lorem_db_seeder.voucher_transaction_min'),
                        (int) config('forus.seeders.lorem_db_seeder.voucher_transaction_max')
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
        $out = [];
        $nth= 1;

        $fields = array_merge($fields, [
            'is_validator' => $prefix === 'Validator',
            'is_provider' => $prefix === 'Provider',
        ]);

        while ($count-- > 0) {
            $out[] = $this->makeOrganization(
                sprintf('%s #%s', $prefix, $nth++),
                $identity_address,
                $fields,
                $offices_count
            );
        }

        return $out;
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
        $organization = Organization::create(array_only(array_merge([
            'kvk' => '69599068',
            'iban' => $this->config('default_organization_iban') ?: Payment::iban('NL'),
            'phone' => '123456789',
            'email' => $this->primaryEmail,
            'bsn_enabled' => true,
            'phone_public' => true,
            'email_public' => true,
            'business_type_id' => BusinessType::pluck('id')->random(),
            'manage_provider_products' => in_array($name, $this->sponsorsWithSponsorProducts),
            'backoffice_available' => in_array($name, $this->sponsorsWithBackoffice),
            'allow_custom_fund_notifications' => in_array($name, $this->organizationsWithCustomNotifications),
        ], $fields, compact('name', 'identity_address')), [
            'name', 'iban', 'email', 'phone', 'kvk', 'btw', 'website',
            'email_public', 'phone_public', 'website_public',
            'identity_address', 'business_type_id', 'manage_provider_products',
            'backoffice_available', 'bsn_enabled', 'is_sponsor', 'is_provider', 'is_validator',
            'allow_custom_fund_notifications',
        ]));

        OrganizationCreated::dispatch($organization);

        $this->makeOffices($organization, $offices_count);

        return $organization;
    }

    /**
     * @param Organization $organization
     * @param int $count
     * @param array $fields
     * @return array
     * @throws \Throwable
     */
    public function makeOffices(
        Organization $organization,
        int $count = 1,
        array $fields = []
    ): array {
        $out = [];

        while ($count-- > 0) {
            $out[] = $this->makeOffice($organization, $fields);
        }

        return $out;
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

        $office = Office::create(array_merge([
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

        foreach (range(0, 4) as $week_day) {
            $office->schedules()->create([
                'week_day' => $week_day,
                'start_time' => '08:00',
                'end_time' => '16:00'
            ]);
        }

        return $office;
    }

    /**
     * @param Organization $organization
     * @param bool $active
     * @param array $fields
     * @return Fund
     * @throws \Throwable
     */
    public function makeFund(
        Organization $organization,
        bool $active = false,
        array $fields = []
    ): Fund {
        $nth = 0;

        do {
            $nth++;
            $fundName = $organization->name . ($nth === 1 ? '' : (' ' . $this->integerToRoman($nth)));
        } while(Fund::whereName($fundName)->count() > 0);

        /** @var \App\Models\Employee$validator $validator */
        $validator = $organization->employeesOfRoleQuery('validation')->firstOrFail();
        $criteriaEditable = in_array($fundName, $this->fundsWithCriteriaEditableAfterLaunch);
        $autoValidation = $this->isUsingAutoValidation($fundName);

        $fund = $organization->createFund(array_merge([
            'name'                          => $fundName,
            'criteria_editable_after_start' => $criteriaEditable,
            'start_date'                    => Carbon::now()->format('Y-m-d'),
            'end_date'                      => Carbon::now()->addDays(60)->format('Y-m-d'),
            'state'                         => $active ? Fund::STATE_ACTIVE : Fund::STATE_WAITING,
            'notification_amount'           => 10000,
            'auto_requests_validation'      => $autoValidation,
            'default_validator_employee_id' => $autoValidation ? $validator->id : null,
            'type'                          => in_array(
                $fundName,
                $this->subsidyFunds
            ) ? Fund::TYPE_SUBSIDIES : Fund::TYPE_BUDGET,
        ], $fields));

        $transaction = $fund->getOrCreateTopUp()->transactions()->create([
            'bank_transaction_id' => "XXXX",
            'amount' => 100000,
        ]);

        FundCreatedEvent::dispatch($fund);
        FundBalanceLowEvent::dispatch($fund);
        FundBalanceSuppliedEvent::dispatch($fund, $transaction);

        if ($active) {
            FundStartedEvent::dispatch($fund);
            FundEndedEvent::dispatch($fund);
            FundStartedEvent::dispatch($fund);
        }

        return $fund;
    }

    /**
     * @param string $key
     * @param string $name
     * @param Organization|null $organization
     * @return Implementation|Model
     */
    public function makeImplementation(
        string $key,
        string $name,
        ?Organization $organization = null,
    ): Implementation|Model {
        $informalCommunication = array_map("str_slug", $this->implementationsWithInformalCommunication);
        $requiredDigId = array_map("str_slug", $this->implementationsWithRequiredDigId);
        $digidSignup = array_map("str_slug", $this->implementationsWithDigidSignup);

        return Implementation::forceCreate([
            'key'   => $key,
            'name'  => $name,
            'organization_id' => $organization?->id,

            'url_webshop' => str_var_replace(
                config('forus.seeders.lorem_db_seeder.url_webshop'),
                compact('key')
            ),
            'url_sponsor' => str_var_replace(
                config('forus.seeders.lorem_db_seeder.url_sponsor'),
                compact('key')
            ),
            'url_provider' => str_var_replace(
                config('forus.seeders.lorem_db_seeder.url_provider'),
                compact('key')
            ),
            'url_validator' => str_var_replace(
                config('forus.seeders.lorem_db_seeder.url_validator'),
                compact('key')
            ),
            'url_app' => str_var_replace(
                config('forus.seeders.lorem_db_seeder.url_app'),
                compact('key')
            ),

            'informal_communication'    => in_array($key, $informalCommunication, true),
            'digid_enabled'             => config('forus.seeders.lorem_db_seeder.digid_enabled'),
            'digid_required'            => in_array($key, $requiredDigId, true),
            'digid_app_id'              => config('forus.seeders.lorem_db_seeder.digid_app_id'),
            'digid_shared_secret'       => config('forus.seeders.lorem_db_seeder.digid_shared_secret'),
            'digid_a_select_server'     => config('forus.seeders.lorem_db_seeder.digid_a_select_server'),
            'digid_sign_up_allowed'     => in_array($key, $digidSignup, true),
            'digid_trusted_cert'        => config('forus.seeders.lorem_db_seeder.digid_trusted_cert'),
            'productboard_api_key'      => config('forus.seeders.lorem_db_seeder.productboard_api_key'),
        ]);
    }

    /**
     * @param Fund $fund
     * @param Implementation $implementation
     * @param string $key
     * @param array $fields
     * @return FundConfig
     */
    public function fundConfigure(
        Fund $fund,
        Implementation $implementation,
        string $key,
        array $fields = []
    ): FundConfig {
        $hashBsn = in_array($fund->name, $this->fundsWithPhysicalCards, true);
        $emailRequired = !in_array($fund->name, $this->fundsWithoutEmailRequirement);

        $backofficeConfig = in_array(
            $fund->organization->name,
            $this->sponsorsWithBackoffice
        ) ? $this->getBackofficeConfigs() : [];

        /** @var FundConfig $fundConfig */
        $fundConfig = $fund->fund_config()->create(array_merge([
            'implementation_id'         => $implementation->id,
            'key'                       => $key,
            'bunq_sandbox'              => true,
            'csv_primary_key'           => 'uid',
            'is_configured'             => true,
            'allow_physical_cards'      => in_array($fund->name, $this->fundsWithPhysicalCards),
            'allow_direct_payments'     => in_array($fund->name, $this->fundsWithDirectPayments),
            'allow_generator_direct_payments' => in_array($fund->name, $this->fundsWithDirectPayments),
            'allow_voucher_top_ups'     => in_array($fund->name, $this->fundsWithVoucherTopUp),
            'email_required'            => $emailRequired,
            'contact_info_enabled'      => $emailRequired,
            'contact_info_required'     => $emailRequired,
            'hash_bsn'                  => $hashBsn,
            'hash_bsn_salt'             => $hashBsn ? $fund->name : null,
            'bunq_key'                  => config('forus.seeders.lorem_db_seeder.bunq_key'),
            'iconnect_api_oin'          => config('forus.seeders.lorem_db_seeder.iconnect_oin'),
            'iconnect_base_url'         => config('forus.seeders.lorem_db_seeder.iconnect_url'),
            'iconnect_target_binding'   => config('forus.seeders.lorem_db_seeder.iconnect_binding'),
            'iconnect_cert'             => $this->config('iconnect_cert'),
            'iconnect_cert_pass'        => $this->config('iconnect_cert_pass'),
            'iconnect_key'              => $this->config('iconnect_key'),
            'iconnect_key_pass'         => $this->config('iconnect_key_pass'),
            'iconnect_cert_trust'       => $this->config('iconnect_cert_trust'),
        ], array_only($fields, [
            'key', 'bunq_key', 'bunq_allowed_ip', 'bunq_sandbox', 'csv_primary_key', 'is_configured',
        ]), $backofficeConfig));

        $eligibility_key = sprintf("%s_eligible", $fund->load('fund_config')->fund_config->key);
        $criteria = [];

        if (!$fund->isAutoValidatingRequests()) {
            $criteria = array_merge($criteria, config('forus.seeders.lorem_db_seeder.funds_criteria'));
        } else {
            $recordType = RecordType::firstOrCreate([
                'key' => $eligibility_key,
                'type' => 'string',
            ], [
                'name' => "$fund->name eligible",
                'system' => true,
            ]);

            $criteria[] = [
                'record_type_key' => $recordType->key,
                'operator' => '=',
                'value' => 'Ja',
                'show_attachment' => false,
            ];
        }

        $fund->criteria()->createMany($criteria);

        $fund->fund_formulas()->create([
            'type' => 'fixed',
            'amount' => $fund->isTypeBudget() ? config('forus.seeders.lorem_db_seeder.voucher_amount'): 0,
        ]);

        if ($fund->isTypeSubsidy()) {
            $fund->fund_limit_multipliers()->create([
                'record_type_key' => 'children_nth',
                'multiplier' => 1,
            ]);
        }

        return $fundConfig;
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
     * @param Identity $identity
     * @param Fund $fund
     * @param array $records
     */
    public function makePrevalidations(
        Identity $identity,
        Fund $fund,
        array $records = []
    ): void {
        $recordTypes = RecordType::search()->pluck('id', 'key');

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
            $prevalidation = Prevalidation::create([
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

        $csv_primary_key = $fund->fund_config->csv_primary_key;
        $env_lorem_bsn = $this->config('prevalidation_bsn', false);

        while ($count-- > 0) {
            do {
                $primaryKeyValue = random_int(111111, 999999);
            } while (collect($out)->pluck($csv_primary_key)->search($primaryKeyValue) !== false);

            $bsn_value = $env_lorem_bsn && ($count === $bsn_prevalidation_index) ?
                $env_lorem_bsn : $this->randomFakeBsn();

            $bsn_value_partner = $env_lorem_bsn && ($count === $bsn_prevalidation_partner_index) ?
                $env_lorem_bsn : $this->randomFakeBsn();

            $out[] = array_merge($records, [
                $csv_primary_key => $primaryKeyValue,
                'gender' => 'Female',
                'net_worth' => random_int(3, 6) * 100,
                'children_nth' => random_int(3, 5),
            ], $fund->fund_config->hash_bsn ? [
                'bsn_hash' => $fund->getHashedValue($bsn_value),
                'partner_bsn_hash' => $fund->getHashedValue($bsn_value_partner),
            ] : []);
        }

        return $out;
    }

    /**
     * @param Organization $organization
     * @param int $count
     * @param array $fields
     * @return array
     * @throws \Throwable
     */
    public function makeProducts(
        Organization $organization,
        int $count = 10,
        array $fields = []
    ): array {
        $out = [];

        while ($count-- > 0) {
            $out[] = $this->makeProduct($organization, $fields);
        }

        return $out;
    }

    /**
     * @param Organization $organization
     * @param array $fields
     * @return Product
     * @throws \Throwable
     */
    public function makeProduct(
        Organization $organization,
        array $fields = []
    ): Product {
        do {
            $name = 'Product #' . random_int(100000, 999999);
        } while(Product::query()->where('name', $name)->count() > 0);

        $price = random_int(1, 20);
        $unlimited_stock = random_int(1, 10) < 3;
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

        $product = Product::create(array_merge(compact(
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

    private function isUsingAutoValidation($fundName): bool
    {
        return in_array($fundName, $this->fundsWithAutoValidation, true);
    }

    /**
     * @return int
     * @throws \Throwable
     */
    private function randomFakeBsn(): int
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
     * @param $implementations
     * @throws \Throwable
     */
    public function makeOtherImplementations($implementations): void {
        foreach ($implementations as $implementation) {
            $this->makeImplementation(str_slug($implementation), $implementation);
        }

        (new ImplementationsNotificationBrandingSeeder)->run();
    }

    /**
     * Make fund requests
     * @return void
     * @throws \Throwable
     */
    public function makeFundRequests(): void
    {
        $requesters = array_map(fn() => $this->makeIdentity(), range(1, $this->countFundRequests));

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
                    },
                    'fund_criterion_id' => $criterion->id,
                    'record_type_key' => $criterion->record_type_key,
                ])->toArray();

                $fund->makeFundRequest($requester, $records);
            }
        }
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
     * @param $key
     * @param null $default
     * @return \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|mixed
     */
    public function config($key, $default = null): mixed
    {
        return config(sprintf('forus.seeders.lorem_db_seeder.%s', $key), $default);
    }

    /**
     * @param $keys
     * @param null $default
     * @return array
     */
    public function configOnly($keys, $default = null): array
    {
        return array_merge(
            array_fill_keys($keys, $default),
            array_only(config('forus.seeders.lorem_db_seeder'), $keys),
        );
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
    private function makeSponsorsFunds(array $sponsors): void
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
        $countFunds = $this->sponsorsWithMultipleFunds[$sponsor->name] ?? 1;

        while ($countFunds-- > 0) {
            $fund = $this->makeFund($sponsor, true);
            $fundConfig = $this->makeFundConfig($fund);

            // Make prevalidations
            if (!$fund->auto_requests_validation && $fund->fund_config->allow_prevalidations) {
                $validator = $fund->organization->identity;

                $this->makePrevalidations($validator, $fund, $this->generatePrevalidationData($fund, 10, [
                    $fundConfig->key . '_eligible' => 'Ja',
                ]));
            }
        }
    }

    /**
     * @param Fund $fund
     * @return FundConfig
     */
    private function makeFundConfig(Fund $fund): FundConfig
    {
        $key = str_slug(explode(' ', $fund->name)[0]);
        $name = $fund->name . ' ' . date('Y');
        $fundKey = str_slug($fund->name . '_' . date('Y'));
        $fundKey = $this->fundKeyOverwrite[$fund->name] ?? $fundKey;

        $implementation = Implementation::where(compact('key'))->first();
        $implementation = $implementation ?: $this->makeImplementation($key, $name, $fund->organization);

        return $this->fundConfigure($fund, $implementation, $fundKey);
    }

    /**
     * @throws \Throwable
     */
    protected function appendPhysicalCards()
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
    private function makeVouchers(): void
    {
        $funds = Fund::where(fn(Builder $q) => FundQuery::whereActiveFilter($q))->get();

        foreach ($funds as $fund) {
            for ($i = 1; $i <= $this->vouchersPerFund; ++$i) {
                $identity_address = $this->makeIdentity();
                $note = 'Lorem seeder!';

                $fund->makeVoucher($identity_address, compact('note'));
                $fund->makeFundFormulaProductVouchers($identity_address, compact('note'));
            }
        }
    }
}
