<?php

use App\Models\BusinessType;
use Illuminate\Database\Seeder;
use Carbon\Carbon;
use App\Models\Organization;
use App\Models\ProductCategory;
use App\Models\Office;
use App\Models\Fund;
use App\Models\Product;
use App\Models\Implementation;

/**
 * Class LoremDbSeederDemo
 */
class LoremDbSeederDemo extends Seeder
{
    private $identityRepo;
    private $recordRepo;
    private $mailService;
    private $baseIdentity;
    private $productCategories;
    private $primaryEmail;

    /**
     * LoremDbSeeder constructor.
     */
    public function __construct()
    {
        $this->identityRepo = resolve('forus.services.identity');
        $this->recordRepo = resolve('forus.services.record');
        $this->mailService = resolve('forus.services.mail_notification');
        $this->primaryEmail = env('DB_SEED_BASE_EMAIL', 'example@example.com');
    }

    /**
     * Run the database seeds
     *
     * @throws Exception
     */
    public function run()
    {
        $this->productCategories = ProductCategory::all()->random(min(ProductCategory::count(), 50));
        $countProviders = env('DB_SEED_PROVIDERS', 10);

        $this->info("Making base identity!");

        if ($identity = $this->recordRepo->identityIdByEmail($this->primaryEmail)) {
            $this->baseIdentity = $identity;
            $this->success("Identity reused!\n");
        } else {
            $this->baseIdentity = $this->makeBaseIdentity($this->primaryEmail);
            $this->success("Identity created!\n");
        }

        $this->info("Making Sponsors!");
        $this->makeSponsors($this->baseIdentity);
        $this->success("Sponsors created!\n");

        $this->info("Making Providers!");
        $this->makeProviders($this->baseIdentity, $countProviders);
        $this->success("Providers created!\n");
    }

    /**
     * @param string $primaryEmail
     * @return mixed
     * @throws Exception
     */
    public function makeBaseIdentity(
        string $primaryEmail
    ) {
        $identityAddress = $this->identityRepo->make('1111', [
            'primary_email' => $primaryEmail,
            'given_name' => 'John',
            'family_name' => 'Doe'
        ]);

        $proxy = $this->identityRepo->makeProxy(
            'confirmation_code',
            $identityAddress,
            'active'
        );

        $this->info(sprintf("Base identity access token: \n\"%s\"\n", str_terminal_color($proxy['access_token'], 'green')));

        $this->mailService->addEmailConnection(
            $identityAddress,
            $primaryEmail
        );

        return $identityAddress;
    }

    /**
     * @param string $identity_address
     */
    public function makeSponsors(
        string $identity_address
    ) {
        /** @var Organization[] $organizations */
        $organizations = [
            $this->makeOrganization('Barneveld', $identity_address)
        ];

        foreach ($organizations as $organization) {
            $this->makeOffices($organization, 2);
            $this->makeImplementation(
                str_slug($organization->name),
                $organization->name . ' ' . date('Y')
            );
        }
    }

    /**
     * @param string $identity_address
     * @param int $count
     */
    public function makeProviders(
        string $identity_address,
        int $count = 1
    ) {
        $organizations = $this->makeOrganizations($identity_address, $count);

        foreach ($organizations as $index => $organization) {
            $this->makeOffices($organization, 1);
            $this->makeProducts($organization, 1);
        }

        foreach (Fund::get() as $fund) {
            $this->addProvidersToFund($fund, $organizations);
        }
    }

    /**
     * @param Fund $fund
     * @param null $organizations
     */
    public function addProvidersToFund(Fund $fund, $organizations = null) {
        if (!$organizations) {
            $organizations = Organization::query();
            $organizations = $organizations->where('id', '!=', $fund->organization_id)->get();
            $organizations = collect($organizations)->random(ceil(count($organizations) / 2));
        }

        /** @var Organization $organization */
        foreach ($organizations as $organization) {
            $fund->providers()->create([
                'organization_id'   => $organization->id,
                'state'             => [
                    0 => 'pending',
                    1 => 'approved',
                    2 => 'approved',
                    3 => 'approved',
                    4 => 'declined',
                ][3]
            ]);
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
                'Optisport',
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
        /** @var Organization $organization */
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

        $organization->validators()->create(compact('identity_address'));

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
        /** @var Office $office */
        $office = $organization->offices()->create(
            collect([
                'address'   => 'Osloweg 131, 9723BK, Groningen',
                'phone'     => '0123456789',
                'lon'       => 6.606065989043237 + (rand(-1000, 1000) / 10000),
                'lat'       => 53.21694230132835 + (rand(-1000, 1000) / 10000),
                'parsed'    => true
            ])->merge($fields)->only([
                'address', 'phone', 'lon', 'lat', 'parsed'
            ])->toArray()
        );

        $start_time = '08:00';
        $end_time = '08:00';

        foreach (range(0, 4) as $week_day) {
            $office->schedules()->create(compact(
                'week_day', 'start_time', 'end_time'
            ));
        }

        return $office;
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
        /** @var Implementation $implementation */
        $implementation = Implementation::create([
            'key' => $key,
            'name' => $name,
            'url_webshop'   => "https://demo.$key.forus.io/#!/",
            'url_sponsor'   => "https://demo.$key.forus.io/sponsor/#!/",
            'url_provider'  => "https://demo.$key.forus.io/provider/#!/",
            'url_validator' => "https://demo.$key.forus.io/validator/#!/",
            'url_app'       => "https://demo.$key.forus.io/me/#!/",
        ]);

        return $implementation;
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
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function makeProduct(
        Organization $organization,
        array $fields = []
    ) {
        do {
            $name = 'Zwemzomerabonnement';
        } while(Product::query()->where('name', $name)->count() > 0);

        $price = 20;
        $old_price = 20;
        $total_amount = 100;
        $sold_out = false;
        $expire_at = Carbon::now()->addDays(rand(20, 60));
        $product_category_id = $this->productCategories->pluck('id')->random();

        /** @var Product $product */
        $product = $organization->products()->create(
            collect(compact(
                'name', 'price', 'old_price', 'total_amount', 'sold_out',
                'expire_at', 'product_category_id'
            ))->merge(collect($fields)->only([
                'name', 'price', 'old_price', 'total_amount', 'sold_out',
                'expire_at'
            ]))->toArray()
        );

        $mediaService = resolve('media');
        $fileName = 'lorem';
        $categoryName = 'lorem';

        try {
            if (env('DB_SEED_IMAGES', true)) {
                $product->attachMedia($mediaService->uploadSingle(
                    new \Illuminate\Http\UploadedFile(
                        database_path(
                            '/seeds/resources/products/' . str_slug($categoryName) . '/' . str_slug($fileName) . '.jpg'
                        ), $fileName
                    ), 'product_photo',
                    $organization->identity_address
                ));
            }
        } catch (Exception $exception) {
            $this->success($exception);
        };

        return $product;
    }

    /**
     * @param string $msg
     */
    public function info(
        string $msg
    ) {
        echo str_terminal_color($msg . "\n", 'white');
    }

    /**
     * @param string $msg
     */
    public function success(
        string $msg
    ) {
        echo str_terminal_color($msg . "\n", 'green');
    }

    /**
     * @param string $msg
     */
    public function error(
        string $msg
    ) {
        echo str_terminal_color($msg . "\n", 'red');
    }

    /**
     * @param string $msg
     */
    public function message(
        string $msg
    ) {
        echo str_terminal_color($msg . "\n", 'light_gray');
    }
}
