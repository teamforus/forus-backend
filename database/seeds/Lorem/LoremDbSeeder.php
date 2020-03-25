<?php

use App\Events\Organizations\OrganizationCreated;
use App\Models\BusinessType;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\Organization;
use App\Models\ProductCategory;
use App\Models\Office;
use App\Models\Fund;
use App\Models\Product;
use App\Models\Prevalidation;
use App\Models\Implementation;
use App\Services\Forus\Record\Models\RecordType;

/**
 * Class LoremDbSeeder
 */
class LoremDbSeeder extends Seeder
{
    private $tokenGenerator;
    private $identityRepo;
    private $recordRepo;
    private $mailService;
    private $baseIdentity;
    private $productCategories;
    private $primaryEmail;

    private $implementations = [
        'Zuidhorn', 'Nijmegen', 'Westerkwartier', 'Berkelland',
        'Kerstpakket', 'Noordoostpolder', 'Oostgelre', 'Winterswijk',
    ];

    private $implementationsWithFunds = [
        'Zuidhorn', 'Nijmegen', 'Westerkwartier',
    ];

    /**
     * LoremDbSeeder constructor.
     */
    public function __construct()
    {
        $this->tokenGenerator = resolve('token_generator');
        $this->identityRepo = resolve('forus.services.identity');
        $this->recordRepo = resolve('forus.services.record');
        $this->mailService = resolve('forus.services.notification');

        $this->productCategories = ProductCategory::all();
        $this->primaryEmail = config('forus.seeders.lorem_db_seeder.default_email');
    }

    private function disableEmails() {
        config()->set('mail.disable', true);
    }

    private function enableEmails() {
        config()->set('mail.disable', false);
    }

    /**
     * Run the database seeds
     *
     * @throws Exception
     */
    public function run()
    {
        $this->disableEmails();
        $countProviders = config('forus.seeders.lorem_db_seeder.providers_count');

        $this->productCategories = ProductCategory::all();
        $this->info("Making base identity!");
        $this->baseIdentity = $this->makeBaseIdentity($this->primaryEmail);
        $this->success("Identity created!");

        $this->info("Making Sponsors!");
        $this->makeSponsors($this->baseIdentity);
        $this->success("Sponsors created!");

        $this->info("Making Providers!");
        $this->makeProviders($this->baseIdentity, $countProviders);
        $this->success("Providers created!");

        $this->applyFunds($this->baseIdentity);

        $this->info("Making other implementations!");
        $this->makeOtherImplementations(array_diff(
            $this->implementations,
            $this->implementationsWithFunds
        ));
        $this->success("Other implementations created!");

        $this->enableEmails();
    }

    /**
     * @param string $primaryEmail
     * @return mixed
     * @throws Exception
     */
    public function makeBaseIdentity(
        string $primaryEmail
    ) {
        $identityAddress = $this->identityRepo->make([
            'primary_email' => $primaryEmail,
            'given_name' => 'John',
            'family_name' => 'Doe'
        ]);

        $proxy = $this->identityRepo->makeProxy(
            'confirmation_code',
            $identityAddress,
            'active'
        );

        $this->info("Base identity access token \"{$proxy['access_token']}\"");

        return $identityAddress;
    }

    /**
     * @param string $identity_address
     */
    public function makeSponsors(
        string $identity_address
    ) {
        $self = $this;

        $organizations = collect($this->implementationsWithFunds)->map(function(
            $implementation
        ) use ($self, $identity_address) {
            return $self->makeOrganization($implementation, $identity_address);
        });

        foreach ($organizations as $organization) {
            $this->makeOffices($organization, 2);

            $fund = $this->makeFund($organization, true);

            $implementation = $this->makeImplementation(
                str_slug($fund->name),
                $fund->name . ' ' . date('Y')
            );

            $this->fundConfigure(
                $fund,
                $implementation,
                $implementation->key . '_' . date('Y'), [
                    'bunq_key' => config('forus.seeders.lorem_db_seeder.bunq_key'),
                ]
            );

            $this->makePrevalidations(
                $fund->organization->identity_address,
                $fund,
                $this->generatePrevalidationData('bsn', 10)
            );
        }
    }

    /**
     * @param string $identity_address
     * @param int $count
     */
    public function makeProviders(
        string $identity_address,
        int $count = 10
    ) {
        $organizations = $this->makeOrganizations($identity_address,  $count);

        foreach (collect($organizations)->random(ceil(count($organizations) / 2)) as $organization) {
            /** @var Fund[] $funds */
            $funds = Fund::get()->random(3);

            foreach ($funds as $fund) {
                $fund->providers()->create([
                    'organization_id'   => $organization->id,
                    'allow_budget'      => !![rand(0, 2)],
                    'allow_products'    => !![rand(0, 2)]
                ]);
            }
        }

        foreach ($organizations as $organization) {
            $this->makeOffices($organization, rand(1, 2));
            $this->makeProducts($organization, rand(2, 4));
        }

        foreach (Fund::get() as $fund) {
            $providers = Organization::with('products')->pluck('id');

            if ($fund->provider_organizations_approved()->count() == 0) {
                $fund->provider_organizations_approved()->create([
                    'organization_id'   => $providers->random(),
                    'state'             => 'approved',
                ]);
            }
        }
    }

    /**
     * @param string $identity_address
     */
    public function applyFunds(string $identity_address)
    {
        /** @var Prevalidation[] $prevalidations */
        $prevalidations = Prevalidation::query()->where([
            'state' => 'pending',
            'identity_address' => $identity_address
        ])->get()->groupBy('fund_id')->map(function(\Illuminate\Support\Collection $arr) {
            return $arr->first();
        });

        foreach ($prevalidations as $prevalidation) {
            foreach($prevalidation->prevalidation_records as $record) {
                if ($record->record_type->key == 'bsn') {
                    continue;
                }

                $record = $this->recordRepo->recordCreate(
                    $identity_address,
                    $record->record_type->key,
                    $record->value
                );

                $validationRequest = $this->recordRepo->makeValidationRequest(
                    $identity_address,
                    $record['id']
                );

                $this->recordRepo->approveValidationRequest(
                    $prevalidation->identity_address,
                    $validationRequest['uuid']
                );
            }

            $prevalidation->update([
                'state' => 'used'
            ]);

            $fund = $prevalidation->fund;

            $voucher = $fund->makeVoucher($identity_address);
            $voucher->tokens()->create([
                'address'           => $this->tokenGenerator->address(),
                'need_confirmation' => true,
            ]);
            $voucher->tokens()->create([
                'address'           => $this->tokenGenerator->address(),
                'need_confirmation' => false,
            ]);

            while ($voucher->amount_available > ($voucher->amount / 2)) {
                $voucher->transactions()->create([
                    'amount' => rand(
                        intval(config('forus.seeders.lorem_db_seeder.voucher_transaction_min')),
                        intval(config('forus.seeders.lorem_db_seeder.voucher_transaction_max'))
                    ),
                    'product_id' => null,
                    'address' => $this->tokenGenerator->address(),
                    'organization_id' => $voucher->fund->provider_organizations_approved->pluck('id')->random(),
                ]);
            }
        }
    }

    /**
     * @param string $identity_address
     * @param int $count
     * @param array $fields
     * @return array
     */
    public function makeOrganizations(
        string $identity_address,
        int $count = 1,
        array $fields = []
    ) {
        $out = [];
        $nth= 1;

        while ($count-- > 0) {
            array_push($out, $this->makeOrganization(
                'Provider #' . $nth++,
                $identity_address,
                $fields
            ));
        }

        return $out;
    }

    /**
     * @param string $name
     * @param string $identity_address
     * @param array $fields
     * @return Organization|\Illuminate\Database\Eloquent\Model
     */
    public function makeOrganization(
        string $name,
        string $identity_address,
        array $fields = []
    ) {
        $organization = Organization::create(
            collect(collect([
                'kvk' => '69599068',
                'iban' => 'NL25BUNQ9900069099',
                'phone' => '123456789',
                'email' => $this->primaryEmail,
                'phone_public' => true,
                'email_public' => true,
                'business_type_id' => BusinessType::pluck('id')->random(),
            ])->merge($fields)->merge(
                compact('name', 'identity_address')
            )->only([
                'name', 'iban', 'email', 'phone', 'kvk', 'btw', 'website',
                'email_public', 'phone_public', 'website_public',
                'identity_address', 'business_type_id'
            ]))->toArray()
        );

        OrganizationCreated::dispatch($organization);

        $organization->offices()->delete();
        $this->makeOffices($organization, rand(2, 3));

        return $organization;
    }

    /**
     * @param Organization $organization
     * @param int $count
     * @param array $fields
     * @return array
     */
    public function makeOffices(
        Organization $organization,
        int $count = 1,
        array $fields = []
    ) {
        $out = [];

        while ($count-- > 0) {
            array_push($out, $this->makeOffice($organization, $fields));
        }

        return $out;
    }

    /**
     * @param Organization $organization
     * @param array $fields
     * @return Office
     */
    public function makeOffice(
        Organization $organization,
        array $fields = []
    ) {
        $office = Office::create(array_merge([
            'organization_id'   => $organization->id,
            'address'           => 'Osloweg 131, 9723BK, Groningen',
            'phone'             => '0123456789',
            'lon'               => 6.606065989043237 + (rand(-1000, 1000) / 10000),
            'lat'               => 53.21694230132835 + (rand(-1000, 1000) / 10000),
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
     * @return Fund|\Illuminate\Database\Eloquent\Model
     */
    public function makeFund(
        Organization $organization,
        bool $active = false,
        array $fields = []
    ) {
        $flag = false;

        do {
            $fundName = $organization->name . ($flag ? (' - ' . rand(0, 999)) : '');
            $flag = true;
        } while(Fund::query()->where('name', $fundName)->count() > 0);

        return $organization->createFund(array_merge([
            'name'          => $fundName,
            'start_date'    => Carbon::now()->format('Y-m-d'),
            'end_date'      => Carbon::now()->addDays(60)->format('Y-m-d'),
            'state'         => $active ? Fund::STATE_ACTIVE : Fund::STATE_WAITING
        ], $fields));
    }

    /**
     * @param string $key
     * @param string $name
     * @return Implementation|\Illuminate\Database\Eloquent\Model
     */
    public function makeImplementation(
        string $key,
        string $name
    ) {
        return Implementation::create([
            'key'   => $key,
            'name'  => $name,

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

            'digid_enabled'         => config('forus.seeders.lorem_db_seeder.digid_enabled'),
            'digid_app_id'          => config('forus.seeders.lorem_db_seeder.digid_app_id'),
            'digid_shared_secret'   => config('forus.seeders.lorem_db_seeder.digid_shared_secret'),
            'digid_a_select_server' => config('forus.seeders.lorem_db_seeder.digid_a_select_server'),
        ]);
    }

    /**
     * @param Fund $fund
     * @param Implementation $implementation
     * @param string $key
     * @param array $fields
     */
    public function fundConfigure(
        Fund $fund,
        Implementation $implementation,
        string $key,
        array $fields = []
    ) {
        $fund->fund_config()->create(collect([
            'implementation_id'     => $implementation->id,
            'key'                   => $key,
            'bunq_sandbox'          => true,
            'csv_primary_key'       => 'uid',
            'is_configured'         => true
        ])->merge(collect($fields)->only([
            'key', 'bunq_key', 'bunq_allowed_ip', 'bunq_sandbox',
            'csv_primary_key', 'is_configured'
        ]))->toArray());

        $eligibility_key = sprintf("%s %s eligible", $fund->name, date('Y'));
        $criteria = config('forus.seeders.lorem_db_seeder.funds_criteria');

        if ($fund->id == 1) {
            /** @var RecordType $recordType */
            $recordType = RecordType::firstOrCreate([
                'key'       => str_slug($eligibility_key, '_'),
                'type'      => 'string',
            ])->updateModel([
                'name'      => $eligibility_key,
            ]);

            array_push($criteria, [
                'record_type_key'   => $recordType->key,
                'operator'          => '=',
                'value'             => 'Ja',
            ]);
        } elseif ($fund->id == 2) {
            /** @var RecordType $recordType */
            $recordType = RecordType::firstOrCreate([
                'key'       => str_slug($eligibility_key . '_nth', '_'),
                'type'      => 'number',
            ])->updateModel([
                'name'      => $eligibility_key . ' nth',
            ]);

            array_push($criteria, [
                'record_type_key'   => $recordType->key,
                'operator'          => '>',
                'value'             => '0',
            ]);
        }

        $fund->criteria()->createMany($criteria);

        $fund->fund_formulas()->create([
            'type'      => 'fixed',
            'amount'    => config('forus.seeders.lorem_db_seeder.voucher_amount')
        ]);
    }

    /**
     * @param string $identity_address
     * @param Fund $fund
     * @param array $records
     */
    public function makePrevalidations(
        string $identity_address,
        Fund $fund,
        array $records = []
    ) {
        $recordTypes = collect(
            $this->recordRepo->getRecordTypes()
        )->pluck('id', 'key');

        collect($records)->map(function($record) use ($fund, $recordTypes) {
            $record = collect($record);

            return $record->map(function($value, $key) use ($recordTypes) {
                $record_type_id = isset($recordTypes[$key]) ? $recordTypes[$key] : null;

                if (!$record_type_id || $key == 'primary_email') {
                    return false;
                }

                return compact('record_type_id', 'value');
            })->filter(function($value) {
                return !!$value;
            })->values();
        })->filter(function($records) {
            return collect($records)->count();
        })->map(function($records) use ($fund, $identity_address) {
            do {
                $uid = $this->tokenGenerator->generate(4, 2);
            } while(Prevalidation::query()->where(
                'uid', $uid
            )->count() > 0);

            $prevalidation = Prevalidation::create([
                'uid' => $uid,
                'state' => 'pending',
                'fund_id' => $fund->id,
                'identity_address' => $identity_address
            ]);

            foreach ($records as $record) {
                $prevalidation->prevalidation_records()->create($record);
            }

            return $prevalidation;
        });
    }

    /**
     * @param string $primaryKey
     * @param int $count
     * @param array $records
     * @param callable|null $primaryKeyGenerator
     * @return array
     */
    public function generatePrevalidationData(
        string $primaryKey,
        int $count = 10,
        array $records = [],
        callable $primaryKeyGenerator = null
    ) {
        $out = [];

        while ($count-- > 0) {
            do {
                $primaryKeyValue = is_callable($primaryKeyGenerator) ? $primaryKeyGenerator() : rand(100000, 999999);
            } while (collect($out)->pluck($primaryKey)->search($primaryKeyValue) !== false);

            array_push($out, collect($records)->merge([
                $primaryKey     => $primaryKeyValue,
                'children_nth'  => rand(3, 5),
                'gender'        => 'Female',
                'net_worth'     => rand(3, 6) * 100,
            ])->toArray());
        };

        return $out;
    }

    /**
     * @param Organization $organization
     * @param int $count
     * @param array $fields
     * @return array
     */
    public function makeProducts(
        Organization $organization,
        int $count = 5,
        array $fields = []
    ) {
        $out = [];

        while ($count-- > 0) {
            array_push($out, $this->makeProduct($organization, $fields));
        }

        return $out;
    }

    /**
     * @param Organization $organization
     * @param array $fields
     * @return Product
     */
    public function makeProduct(
        Organization $organization,
        array $fields = []
    ) {
        do {
            $name = 'Product #' . rand(100000, 999999);
        } while(Product::query()->where('name', $name)->count() > 0);

        $price = rand(1, 20);
        $old_price = rand($price, 50);
        $total_amount = rand(1, 10) * 10;
        $sold_out = false;
        $expire_at = Carbon::now()->addDays(rand(20, 60));
        $product_category_id = $this->productCategories->pluck('id')->random();

        return $product = Product::create(
            collect(array_merge(compact(
                'name', 'price', 'old_price', 'total_amount', 'sold_out',
                'expire_at', 'product_category_id'
            ), [
                'organization_id' => $organization->id
            ]))->merge(collect($fields)->only([
                'name', 'price', 'old_price', 'total_amount', 'sold_out',
                'expire_at'
            ]))->toArray()
        );
    }


    public function makeOtherImplementations($implementations) {
        foreach ($implementations as $implementation) {
            $this->makeImplementation(str_slug($implementation), $implementation);
        }
    }

    /**
     * @param string $msg
     */
    public function info(
        string $msg
    ) {
        echo "\e[0;34m{$msg}\e[0m\n";;
    }

    /**
     * @param string $msg
     */
    public function success(
        string $msg
    ) {
        echo "\e[0;32m{$msg}\e[0m\n";;
    }

    /**
     * @param string $msg
     */
    public function error(
        string $msg
    ) {
        echo "\e[0;31m{$msg}\e[0m\n";;
    }
}
