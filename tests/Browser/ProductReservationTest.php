<?php

namespace Tests\Browser;

use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\OrganizationReservationField;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\Voucher;
use App\Scopes\Builders\FundProviderQuery;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Facebook\WebDriver\Exception\ElementClickInterceptedException;
use Facebook\WebDriver\Exception\NoSuchElementException;
use Facebook\WebDriver\Exception\TimeOutException;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\DuskTestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFundProviders;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestIdentities;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestProducts;
use Throwable;

class ProductReservationTest extends DuskTestCase
{
    use WithFaker;
    use MakesTestFunds;
    use AssertsSentEmails;
    use MakesTestProducts;
    use HasFrontendActions;
    use MakesTestIdentities;
    use MakesTestOrganizations;
    use MakesTestFundProviders;
    use MakesProductReservations;

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationSimple(): void
    {
        $fund = $this->makeTestFund(Implementation::byKey('nijmegen')->organization);

        try {
            $provider = $this->makeTestProviderOrganization($this->makeIdentity());
            $product = $this->makeTestProductForReservation($provider);
            $identity = $this->makeIdentity($this->makeUniqueEmail());

            $fund->makeVoucher($identity);
            $this->makeTestFundProvider($provider, $fund);
            $this->assertFundHasApprovedProviders($fund);

            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ]);
        } finally {
            $fund->archive($fund->organization->employees[0]);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationCustomFields(): void
    {
        $fund = $this->makeTestFund(Implementation::byKey('nijmegen')->organization);

        try {
            $provider = $this->makeTestProviderOrganization($this->makeIdentity());
            $product = $this->makeTestProductForReservation($provider);
            $identity = $this->makeIdentity($this->makeUniqueEmail());

            $fund->makeVoucher($identity);
            $this->makeTestFundProvider($provider, $fund);
            $this->assertFundHasApprovedProviders($fund);

            $product->forceFill([
                'reservation_fields' => true,
            ])->save();

            $customFields = [[
                'label' => 'custom field text 1',
                'type' => OrganizationReservationField::TYPE_TEXT,
                'description' => 'custom field text description 1',
                'required' => true,
                'value' => 'some text',
            ], [
                'label' => 'custom field text 2',
                'type' => OrganizationReservationField::TYPE_TEXT,
                'description' => null,
                'required' => false,
                'value' => null,
            ], [
                'label' => 'custom field number 1',
                'type' => OrganizationReservationField::TYPE_NUMBER,
                'description' => 'custom field number description 1',
                'required' => true,
                'value' => 100,
            ], [
                'label' => 'custom field number 2',
                'type' => OrganizationReservationField::TYPE_NUMBER,
                'description' => null,
                'required' => false,
                'value' => null,
            ], [
                'label' => 'custom field bool 1',
                'type' => OrganizationReservationField::TYPE_BOOLEAN,
                'description' => 'custom field bool description 1',
                'required' => true,
                'value' => 'Ja',
            ], [
                'label' => 'custom field bool 2',
                'type' => OrganizationReservationField::TYPE_BOOLEAN,
                'description' => null,
                'required' => false,
                'value' => null,
            ]];

            $fields = [];

            foreach ($customFields as $order => $item) {
                $field = $provider->reservation_fields()->create([
                    ...Arr::only($item, ['label', 'type', 'description', 'required']),
                    'order' => $order,
                ]);

                $fields[] = [
                    ...$item,
                    'id' => $field->id,
                    'field_type' => 'custom',
                    'dusk' => "@customField$field->id",
                    'dusk_description_btn' => "@customField{$field->id}InfoBtn",
                ];
            }

            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ], otherFields: $fields);

            // Assert if reservation_fields is false - no custom fields used
            $product->forceFill([
                'reservation_fields' => false,
            ])->save();

            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ]);
        } finally {
            $fund->archive($fund->organization->employees[0]);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationPhoneField(): void
    {
        $fund = $this->makeTestFund(Implementation::byKey('nijmegen')->organization);

        try {
            $fieldsOptional = [[
                'id' => 'phone',
                'type' => 'text',
                'dusk' => '@productReserveFormPhone',
                'value' => null,
                'required' => false,
                'field_type' => 'phone',
            ]];

            $fieldsRequired = [[
                'id' => 'phone',
                'type' => 'text',
                'dusk' => '@productReserveFormPhone',
                'value' => '1234545678',
                'required' => true,
                'field_type' => 'phone',
            ]];

            $provider = $this->makeTestProviderOrganization($this->makeIdentity());
            $product = $this->makeTestProductForReservation($provider);
            $identity = $this->makeIdentity($this->makeUniqueEmail());

            $product->forceFill([
                'reservation_phone' => Product::RESERVATION_FIELD_OPTIONAL,
                'reservation_fields' => true,
            ])->save();

            $fund->makeVoucher($identity);
            $this->makeTestFundProvider($provider, $fund);
            $this->assertFundHasApprovedProviders($fund);

            // Test reservation with optional phone field
            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ], otherFields: $fieldsOptional);

            $product->forceFill([
                'reservation_phone' => Product::RESERVATION_FIELD_REQUIRED,
                'reservation_fields' => true,
            ])->save();

            // Test required reservation phone
            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ], otherFields: $fieldsRequired);

            // Set global configs for phone
            $product->forceFill([
                'reservation_phone' => Product::RESERVATION_FIELD_GLOBAL,
                'reservation_fields' => true,
            ])->save();

            $provider->forceFill([
                'reservation_phone' => Product::RESERVATION_FIELD_OPTIONAL,
            ])->save();

            // Test reservation with global optional phone field
            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ], otherFields: $fieldsOptional);

            $provider->forceFill([
                'reservation_phone' => Product::RESERVATION_FIELD_REQUIRED,
            ])->save();

            // Test global required reservation phone
            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ], otherFields: $fieldsRequired);

            // Assert if reservation_fields is false - no phone field used
            $product->forceFill([
                'reservation_phone' => Product::RESERVATION_FIELD_REQUIRED,
                'reservation_fields' => false,
            ])->save();

            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ]);
        } finally {
            $fund->archive($fund->organization->employees[0]);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationBirthDateField(): void
    {
        $fund = $this->makeTestFund(Implementation::byKey('nijmegen')->organization);

        try {
            $fieldsOptional = [[
                'id' => 'birth_date',
                'type' => 'date',
                'dusk' => '@birthDate',
                'value' => null,
                'required' => false,
                'field_type' => 'birth_date',
            ]];

            $fieldsRequired = [[
                'id' => 'birth_date',
                'type' => 'date',
                'dusk' => '@birthDate',
                'value' => '10-01-1980',
                'required' => true,
                'field_type' => 'birth_date',
            ]];

            $provider = $this->makeTestProviderOrganization($this->makeIdentity());
            $product = $this->makeTestProductForReservation($provider);
            $identity = $this->makeIdentity($this->makeUniqueEmail());

            $product->forceFill([
                'reservation_birth_date' => Product::RESERVATION_FIELD_OPTIONAL,
                'reservation_fields' => true,
            ])->save();

            $fund->makeVoucher($identity);
            $this->makeTestFundProvider($provider, $fund);
            $this->assertFundHasApprovedProviders($fund);

            // Test reservation with optional birth_date field
            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ], otherFields: $fieldsOptional);

            $product->forceFill([
                'reservation_birth_date' => Product::RESERVATION_FIELD_REQUIRED,
                'reservation_fields' => true,
            ])->save();

            // Test required reservation birth_date
            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ], otherFields: $fieldsRequired);

            // Set global configs for birth_date
            $product->forceFill([
                'reservation_birth_date' => Product::RESERVATION_FIELD_GLOBAL,
                'reservation_fields' => true,
            ])->save();

            $provider->forceFill([
                'reservation_birth_date' => Product::RESERVATION_FIELD_OPTIONAL,
            ])->save();

            // Test reservation with global optional birth_date field
            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ], otherFields: $fieldsOptional);

            $provider->forceFill([
                'reservation_birth_date' => Product::RESERVATION_FIELD_REQUIRED,
            ])->save();

            // Test global required reservation birth_date
            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ], otherFields: $fieldsRequired);

            // Assert if reservation_fields is false - no birth_date field used
            $product->forceFill([
                'reservation_birth_date' => Product::RESERVATION_FIELD_REQUIRED,
                'reservation_fields' => false,
            ])->save();

            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ]);
        } finally {
            $fund->archive($fund->organization->employees[0]);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationRequiredAddress(): void
    {
        $fund = $this->makeTestFund(Implementation::byKey('nijmegen')->organization);

        try {
            $provider = $this->makeTestProviderOrganization($this->makeIdentity());
            $product = $this->makeTestProductForReservation($provider);
            $identity = $this->makeIdentity($this->makeUniqueEmail());

            $product->forceFill([
                'reservation_address' => Product::RESERVATION_FIELD_OPTIONAL,
                'reservation_fields' => true,
            ])->save();

            $fund->makeVoucher($identity);
            $this->makeTestFundProvider($provider, $fund);
            $this->assertFundHasApprovedProviders($fund);

            $addressData = [
                'city' => 'Kraigmouth',
                'street' => 'Hodkiewicz Parks',
                'house_nr' => '8',
                'house_nr_addition' => 'A',
                'postal_code' => '1234AB',
            ];

            // Test reservation without optional address when no address is saved
            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ], [...$addressData, 'existing' => false, 'optional' => true]);

            $product->forceFill([
                'reservation_address' => Product::RESERVATION_FIELD_REQUIRED,
                'reservation_fields' => true,
            ])->save();

            // Test required reservation address without saved address
            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ], [...$addressData, 'existing' => false]);

            // Test required reservation address with saved address
            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ], [...$addressData, 'existing' => true]);

            // Test required reservation address with saved address
            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ], [...$addressData, 'existing' => true, 'existing_update' => true]);

            $product->forceFill([
                'reservation_address' => Product::RESERVATION_FIELD_OPTIONAL,
                'reservation_fields' => true,
            ])->save();

            // Test required reservation address with saved address
            $this->assertProductCanBeReservedByIdentity($fund, $product, $identity, [
                'first_name' => $this->faker->firstName,
                'last_name' => $this->faker->lastName,
            ], [...$addressData, 'existing' => true, 'optional' => true, 'skip' => true]);
        } finally {
            $fund->archive($fund->organization->employees[0]);
        }
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReservationState(): void
    {
        Cache::clear();

        $implementation = Implementation::general();
        $this->assertNotNull($implementation, 'Implementation not found.');

        $organization = Organization::where('name', 'Nijmegen')->first();

        $reservation = $this->makeBudgetReservationInDb($organization);
        $reservation = ProductReservation::find($reservation->id);

        $this->browse(function (Browser $browser) use ($implementation, $reservation) {
            $provider = $reservation->product->organization;
            $identity = $provider->identity;
            $this->assertNotNull($identity);

            $browser->visit($implementation->urlFrontend('provider'));

            // Authorize identity
            $this->loginIdentity($browser, $identity);
            $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $identity);
            $this->selectDashboardOrganization($browser, $provider);

            $this->goToReservationsPage($browser);
            $this->checkReservationState($browser, $reservation);

            if ($reservation->isPending()) {
                $reservation->acceptProvider();
                $reservation = ProductReservation::find($reservation->id);

                $this->assertSame(ProductReservation::STATE_ACCEPTED, $reservation->state);

                $browser->refresh();
                $this->checkReservationState($browser, $reservation);
            }

            if ($reservation->isAccepted()) {
                $reservation->rejectOrCancelProvider();
                $reservation = ProductReservation::find($reservation->id);

                $this->assertSame(ProductReservation::STATE_CANCELED_BY_PROVIDER, $reservation->state);

                $browser->refresh();
                $this->checkReservationState($browser, $reservation);
            }

            // Logout
            $this->logout($browser);
        });
    }

    /**
     * @param Fund $fund
     * @return void
     */
    protected function assertFundHasApprovedProviders(Fund $fund): void
    {
        // Authorize identity
        $funds = Fund::query()
            ->where('id', $fund->id)
            ->get()
            ->filter(fn (Fund $fund) => FundProviderQuery::whereApprovedForFundsFilter(
                FundProvider::query(),
                $fund->id,
            )->exists());

        // Assert at lease one fund exist
        $this->assertCount(1, $funds, 'Fund should have approved providers.');
    }

    /**
     * @param Identity $identity
     * @param Product $product
     * @param Fund $fund
     * @param array $data
     * @return ProductReservation
     */
    protected function findProductReservation(
        Identity $identity,
        Product $product,
        Fund $fund,
        array $data,
    ): ProductReservation {
        // Assert reservation is created
        $productReservation = ProductReservation::query()
            ->where($data)
            ->whereRelation('voucher.identity', 'address', $identity->address)
            ->whereRelation('voucher.fund', 'id', $fund->id)
            ->whereRelation('product', 'id', $product->id)
            ->first();

        self::assertNotEmpty($productReservation);

        return $productReservation;
    }

    /**
     * @param Browser $browser
     * @param ProductReservation $reservation
     * @throws TimeoutException
     * @return void
     */
    private function checkReservationState(Browser $browser, ProductReservation $reservation): void
    {
        $browser
            ->waitFor("@tableReservationRow$reservation->id")
            ->assertSeeIn("@tableReservationRow$reservation->id @reservationState", $reservation->state_locale);
    }

    /**
     * @param Fund $fund
     * @param Product $product
     * @param Identity $identity
     * @param array|null $userData
     * @param array|null $addressData
     * @param array|null $otherFields
     * @throws Throwable
     * @return void
     */
    private function assertProductCanBeReservedByIdentity(
        Fund $fund,
        Product $product,
        Identity $identity,
        array $userData = null,
        array $addressData = null,
        array $otherFields = null,
    ): void {
        Cache::clear();
        $implementation = $fund->getImplementation();

        $this->browse(function (Browser $browser) use (
            $implementation,
            $identity,
            $fund,
            $userData,
            $addressData,
            $product,
            $otherFields
        ) {
            $browser->visit($implementation->urlWebshop());

            $this->loginAndGoToFundVoucher($browser, $identity, $fund);
            $this->openFirstProductAvailableForVoucher($browser, $fund, $product);

            $browser->waitFor('@productName');
            $browser->assertSeeIn('@productName', $product->name);

            $this->openReservationModal($browser, $fund);
            $this->skipReservationModalEmailAndSelectVoucher($browser, $identity);

            $this->fillReservationModalNameAndLastName(
                $browser,
                $userData['first_name'],
                $userData['last_name'],
                !!count($otherFields ?? [])
            );

            if ($otherFields) {
                $this->fillReservationModalCustomFields($browser, $otherFields);
            }

            if ($addressData) {
                $this->fillReservationModalAddress($browser, $addressData);
            }

            $this->fillReservationModalNote($browser);

            $this->assertReservationModalConfirmationDetails($browser, $userData['first_name'], $addressData, $otherFields);
            $this->submitReservationModal($browser);

            $reservation = $this->findProductReservation($identity, $product, $fund, $userData);

            $this->assertReservationCreatedWithProperAcceptanceStatus($reservation);
            $this->cancelReservation($browser, $reservation);

            // Logout user
            $this->logout($browser);
        });
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @param Product $product
     * @throws TimeoutException
     * @return void
     */
    private function openFirstProductAvailableForVoucher(Browser $browser, Fund $fund, Product $product): void
    {
        $browser->waitFor("@listProductsRow$product->id")->press("@listProductsRow$product->id");
        $browser->waitFor("@listFundsRow$fund->id");
    }

    /**
     * @param Browser $browser
     * @param Fund $fund
     * @throws ElementClickInterceptedException
     * @throws NoSuchElementException
     * @throws TimeoutException
     * @return void
     */
    private function openReservationModal(Browser $browser, Fund $fund): void
    {
        // Find available fund and reserve product
        $browser->click("@listFundsRow$fund->id @reserveProduct");

        // Wait for the reservation modal and submit with no data
        $browser->waitFor('@modalProductReserve');
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @param Fund $fund
     * @throws TimeoutException
     * @return void
     */
    private function loginAndGoToFundVoucher(Browser $browser, Identity $identity, Fund $fund): void
    {
        $this->loginIdentity($browser, $identity);
        $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

        $browser->waitFor('@headerTitle');

        $this->goToVouchersPage($browser, $identity);
        $this->goToVoucherPage($browser, $fund->vouchers()->where('identity_id', $identity->id)->first());
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @throws TimeoutException
     * @return void
     */
    private function skipReservationModalEmailAndSelectVoucher(Browser $browser, Identity $identity): void
    {
        $browser->waitFor('@modalProductReserve');

        $browser->within('@modalProductReserve', function (Browser $browser) use ($identity) {
            if (!$identity->email) {
                $browser->waitFor('@reserveSkipEmailStep');
                $browser->click('@reserveSkipEmailStep');
            }

            $browser->waitFor('@btnSelectVoucher');
            $browser->click('@btnSelectVoucher');
        });
    }

    /**
     * @param Browser $browser
     * @param string $firstName
     * @param string $lastName
     * @param bool $skipSubmit
     * @throws TimeoutException
     * @return void
     */
    private function fillReservationModalNameAndLastName(
        Browser $browser,
        string $firstName,
        string $lastName,
        bool $skipSubmit = false
    ): void {
        $browser->waitFor('@productReserveForm');

        $browser->within('@productReserveForm', function (Browser $browser) use ($firstName, $lastName, $skipSubmit) {
            $browser->press('@btnSubmit');
            $browser->waitFor('.form-error');

            // Fill form with data and submit again
            $browser->type('@productReserveFormFirstName', $firstName);
            $browser->type('@productReserveFormLastName', $lastName);

            if (!$skipSubmit) {
                $browser->press('@btnSubmit');
            }
        });
    }

    /**
     * @param Browser $browser
     * @param array $fields
     * @throws TimeoutException
     * @return void
     */
    private function fillReservationModalCustomFields(Browser $browser, array $fields): void
    {
        $browser->waitFor('@productReserveForm');

        $browser->within('@productReserveForm', function (Browser $browser) use ($fields) {
            foreach ($fields as $field) {
                // if field required - try to submit form and assert form error visible
                if ($field['required']) {
                    $browser->press('@btnSubmit');
                    $browser->waitFor('.form-error');
                }

                // if custom - assert label and description
                if ($field['field_type'] === 'custom') {
                    $browser->assertSee($field['label']);

                    if (!empty($field['description'])) {
                        $browser->click($field['dusk_description_btn']);
                        $browser->waitForText($field['description']);
                    } else {
                        $browser->assertMissing($field['dusk_description_btn']);
                    }
                }

                $browser->waitFor($field['dusk']);

                if (!empty($field['value'])) {
                    switch ($field['type']) {
                        case 'boolean':
                            $browser->click("{$field['dusk']} .select-control-search");
                            $this->findOptionElement($browser, $field['dusk'], $field['value'])->click();
                            break;
                        case 'number':
                        case 'text':
                            $browser->type($field['dusk'], $field['value']);
                            break;
                        case 'date':
                            $browser->type("{$field['dusk']} input[type='text']", $field['value']);
                            break;
                    }
                }
            }

            $browser->press('@btnSubmit');
        });
    }

    /**
     * @param array $data
     * @return string
     */
    private function makeAddressString(array $data): string
    {
        return implode(', ', array_filter([
            $data['city'],
            $data['street'],
            $data['house_nr'],
            $data['house_nr_addition'],
            $data['postal_code'],
        ]));
    }

    /**
     * @param Browser $browser
     * @param array $data
     * @throws TimeoutException
     * @return void
     */
    private function fillReservationModalAddress(Browser $browser, array $data): void
    {
        $browser->waitFor('@productReserveAddress', 100000);

        $browser->within('@productReserveAddress', function (Browser $browser) use ($data) {
            $skip = $data['skip'] ?? false;
            $optional = $data['optional'] ?? false;
            $existing = $data['existing'] ?? false;
            $existingUpdate = $data['existing_update'] ?? false;

            if (!$existing && $optional) {
                $browser->waitFor('@productReserveAddress');
                $browser->assertMissing('@btnSkip');
                $browser->press('@btnSubmit');

                return;
            }

            if (!$existing) {
                $browser->waitFor('@productReserveAddressForm');
                $browser->assertDisabled('@productReserveAddressFormApply');

                // Fill form with data and submit again
                $browser->type('@productReserveFormStreet', $data['street']);
                $browser->type('@productReserveFormHouseNumber', $data['house_nr']);
                $browser->type('@productReserveFormHouseNumberAddition', $data['house_nr_addition']);
                $browser->type('@productReserveFormPostalCode', '---');
                $browser->type('@productReserveFormCity', $data['city']);

                $browser->click('@productReserveAddressFormApply');
                $browser->waitFor('.form-error');

                $browser->click('@productReserveAddressFormClear');
                $browser->assertDisabled('@productReserveAddressFormApply');

                $browser->type('@productReserveFormStreet', $data['street']);
                $browser->type('@productReserveFormHouseNumber', $data['house_nr']);
                $browser->type('@productReserveFormHouseNumberAddition', $data['house_nr_addition']);
                $browser->type('@productReserveFormPostalCode', $data['postal_code']);
                $browser->type('@productReserveFormCity', $data['city']);

                $browser->click('@productReserveAddressFormApply');
                $browser->waitFor('@productReserveAddressPreview');

                $browser->assertSeeIn('@productReserveAddressPreviewText', $this->makeAddressString($data));

                $browser->waitFor('@productReserveAddressPreviewEdit');
                $browser->assertPresent('@productReserveAddressPreviewEdit');
                $browser->click('@productReserveAddressPreviewEdit');

                $browser->assertPresent('@productReserveAddressFormApply');
                $browser->click('@productReserveAddressFormSave');
            } else {
                $browser->waitFor('@productReserveAddressPreview');
                $browser->assertSeeIn('@productReserveAddressPreviewText', $this->makeAddressString($data));
            }

            $browser->waitFor('@productReserveAddressPreviewEdit');
            $browser->click('@productReserveAddressPreviewEdit');

            $browser->waitFor('@productReserveAddressForm');
            $browser->waitUntilMissing('@productReserveAddressFormApply');
            $browser->assertMissing('@productReserveAddressFormApply');

            if ($existingUpdate) {
                $browser->waitFor('@productReserveFormStreet');
                $browser->clear('@productReserveFormStreet');
                $browser->type('@productReserveFormStreet', 'Sesame');
                $browser->click('@productReserveAddressFormSave');

                $browser->waitForTextIn('@productReserveAddressPreviewText', $this->makeAddressString([
                    ...$data, 'street' => 'Sesame',
                ]));

                $browser->waitFor('@productReserveAddressPreviewEdit');
                $browser->click('@productReserveAddressPreviewEdit');

                $browser->waitFor('@productReserveFormStreet');
                $browser->clear('@productReserveFormStreet');
                $browser->type('@productReserveFormStreet', $data['street']);
                $browser->click('@productReserveAddressFormSave');

                $browser->waitForTextIn('@productReserveAddressPreviewText', $this->makeAddressString($data));
            } else {
                $browser->click('@productReserveAddressFormCancel');
            }

            if ($existing && $optional) {
                $browser->assertPresent('@btnSkip');
            } else {
                $browser->assertMissing('@btnSkip');
            }

            if ($skip) {
                $browser->press('@btnSkip');

                return;
            }

            $browser->press('@btnSubmit');
        });
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    private function fillReservationModalNote(Browser $browser): void
    {
        $browser->waitFor('@productReserveNotes');

        $browser->within('@productReserveNotes', function (Browser $browser) {
            // Fill form with data and submit again
            $browser->type('@productReserveFormNote', $this->faker->text(100));
            $browser->press('@btnSubmit');
        });
    }

    /**
     * @param Browser $browser
     * @param string $firstName
     * @param array|null $address
     * @param array|null $otherFields
     * @throws TimeoutException
     * @return void
     */
    private function assertReservationModalConfirmationDetails(
        Browser $browser,
        string $firstName,
        ?array $address,
        ?array $otherFields
    ): void {
        // Assert success
        $browser->waitForTextIn('@productReserveConfirmDetails', $firstName);

        if (!is_null($address)) {
            if (!Arr::get($address, 'optional', false)) {
                $browser->waitForTextIn('@overviewValueStreet', Arr::get($address, 'street', 'Leeg'));
                $browser->waitForTextIn('@overviewValueHouseNr', Arr::get($address, 'house_nr', 'Leeg'));
                $browser->waitForTextIn('@overviewValueHouseNrAddition', Arr::get($address, 'house_nr_addition', 'Leeg'));
                $browser->waitForTextIn('@overviewValuePostalCode', Arr::get($address, 'postal_code', 'Leeg'));
                $browser->waitForTextIn('@overviewValueCity', Arr::get($address, 'city', 'Leeg'));
            } else {
                $browser->waitForTextIn('@overviewValueStreet', 'Leeg');
                $browser->waitForTextIn('@overviewValueHouseNr', 'Leeg');
                $browser->waitForTextIn('@overviewValueHouseNrAddition', 'Leeg');
                $browser->waitForTextIn('@overviewValuePostalCode', 'Leeg');
                $browser->waitForTextIn('@overviewValueCity', 'Leeg');
            }
        }

        foreach ($otherFields ?? [] as $field) {
            $browser->waitForTextIn(
                "@overviewValueCustomField{$field['id']}",
                empty($field['value']) ? 'Leeg' : $field['value']
            );
        }
    }

    /**
     * @param Browser $browser
     * @throws TimeoutException
     * @return void
     */
    private function submitReservationModal(Browser $browser): void
    {
        $browser->press('@btnConfirmSubmit');

        $browser->waitFor('@productReserveSuccess');
        $browser->within('@productReserveSuccess', fn (Browser $el) => $el->click('@btnReservationFinish'));

        // Assert redirected to reservations list
        $browser->waitFor('@reservationsTitle');
    }

    /**
     * @param ProductReservation $reservation
     * @return void
     */
    private function assertReservationCreatedWithProperAcceptanceStatus(ProductReservation $reservation): void
    {
        $autoAccept = $reservation->product->organization->reservations_auto_accept;
        $stateIsValid = $autoAccept ? $reservation->isAccepted() : $reservation->isPending();

        $this->assertNotNull($reservation, 'Reservation not created');
        $this->assertTrue($stateIsValid, 'Wrong reservation status');
    }

    /**
     * @param Browser $browser
     * @param ProductReservation $reservation
     * @throws TimeoutException
     * @return void
     */
    private function cancelReservation(Browser $browser, ProductReservation $reservation): void
    {
        // find reserved product in list with pending label
        $this->assertReservationElementExists($browser, $reservation);

        // cancel reservation
        $browser->within("@listReservationsRow$reservation->id", fn (Browser $el) => $el->press('@btnCancelReservation'));

        $browser->waitFor('@modalProductReserveCancel');
        $browser->within('@modalProductReserveCancel', fn (Browser $el) => $el->press('@btnSubmit'));

        $browser->waitUntilMissingText($reservation->code);
        $browser->assertMissing("@listReservationsRow$reservation->id");

        $reservation->refresh();
        $this->assertTrue($reservation->isCanceledByClient(), 'Reservation not canceled.');
    }

    /**
     * @param Browser $browser
     * @param ProductReservation $reservation
     * @throws TimeoutException
     * @return void
     */
    private function assertReservationElementExists(
        Browser $browser,
        ProductReservation $reservation,
    ): void {
        $selector = "@listReservationsRow$reservation->id";
        $browser->waitFor($selector);

        $browser->within($selector, function (Browser $browser) use ($reservation) {
            $browser->assertVisible($reservation->isExpired() ? '@labelExpired' : [
                'pending' => '@labelPending',
                'accepted' => '@labelAccepted',
                'rejected' => '@labelRejected',
                'canceled' => '@labelCanceled',
                'canceled_by_client' => '@labelCanceled',
            ][$reservation->state]);

            $browser->assertSeeIn('@reservationProduct', $reservation->product->name);
            $browser->assertSeeIn('@reservationCode', $reservation->code);
        });
    }

    /**
     * @param Browser $browser
     * @param Identity $identity
     * @throws TimeOutException
     * @return void
     */
    private function goToVouchersPage(Browser $browser, Identity $identity): void
    {
        $this->assertIdentityAuthenticatedOnWebshop($browser, $identity);

        $browser->waitFor('@userVouchers');
        $browser->element('@userVouchers')->click();
    }

    /**
     * @param Browser $browser
     * @param Voucher $voucher
     * @throws TimeoutException
     * @return void
     */
    private function goToVoucherPage(Browser $browser, Voucher $voucher): void
    {
        $browser->waitFor("@listVouchersRow$voucher->id");
        $browser->element("@listVouchersRow$voucher->id")->click();

        $browser->waitFor('@voucherTitle');
        $browser->assertSeeIn('@voucherTitle', $voucher->fund->name);
    }
}
