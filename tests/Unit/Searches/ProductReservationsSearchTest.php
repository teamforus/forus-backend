<?php

namespace Tests\Unit\Searches;

use App\Models\ProductReservation;
use App\Models\ReservationField;
use App\Searches\ProductReservationsSearch;
use App\Traits\DoesTesting;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class ProductReservationsSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestVouchers;
    use MakesTestOrganizations;
    use MakesProductReservations;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $search = new ProductReservationsSearch([], ProductReservation::query());

        $this->assertQueryBuilds($search->query());
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByQueryFund(): void
    {
        $fundNamePart1 = 'match';
        $fundNamePart2 = 'other';

        $fundDescriptionTextPart1 = 'second';
        $fundDescriptionTextPart2 = 'third';

        $fundDescriptionShortPart1 = 'next';
        $fundDescriptionShortPart2 = 'previous';

        $organizationNamePart1 = 'first';
        $organizationNamePart2 = 'last';

        $organization1 = $this->makeTestOrganization($this->makeIdentity(), ['name' => "$organizationNamePart1 org"]);
        $organization2 = $this->makeTestOrganization($this->makeIdentity(), ['name' => "$organizationNamePart2 org"]);

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization1, [
            'name' => "$fundNamePart1 fund name",
            'description_text' => "$fundDescriptionTextPart1 fund description",
            'description_short' => "$fundDescriptionShortPart1 fund description short",
        ]);

        $fund2 = $this->makeTestFund($organization2, [
            'name' => "$fundNamePart2 fund name",
            'description_text' => "$fundDescriptionTextPart2 fund description",
            'description_short' => "$fundDescriptionShortPart2 fund description short",
        ]);

        $voucher1 = $this->makeTestVoucher($fund1, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund2, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($provider, [$fund1]);
        $product2 = $this->createProductForReservation($provider, [$fund2]);

        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        // assert by fund name
        $this->assertSearchIds(['q' => $fundNamePart1], [$reservation1->id]);
        $this->assertSearchIds(['q' => $fundNamePart2], [$reservation2->id]);

        // assert by fund description
        $this->assertSearchIds(['q' => $fundDescriptionTextPart1], [$reservation1->id]);
        $this->assertSearchIds(['q' => $fundDescriptionTextPart2], [$reservation2->id]);

        // assert by fund description short
        $this->assertSearchIds(['q' => $fundDescriptionShortPart1], [$reservation1->id]);
        $this->assertSearchIds(['q' => $fundDescriptionShortPart2], [$reservation2->id]);

        // assert by organization name
        $this->assertSearchIds(['q' => $organizationNamePart1], [$reservation1->id]);
        $this->assertSearchIds(['q' => $organizationNamePart2], [$reservation2->id]);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByQueryProduct(): void
    {
        $namePart1 = 'first';
        $namePart2 = 'last';

        $descriptionPart1 = 'unique';
        $descriptionPart2 = 'common';

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);

        $product1->update([
            'name' => "$namePart1 product name",
            'description' => "$descriptionPart1 product description",
        ]);

        $product2 = $this->createProductForReservation($organization, [$fund]);

        $product2->update([
            'name' => "$namePart2 product name",
            'description' => "$descriptionPart2 product description",
        ]);

        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        $this->assertSearchIds(['q' => $namePart1], [$reservation1->id]);
        $this->assertSearchIds(['q' => $descriptionPart1], [$reservation1->id]);

        $this->assertSearchIds(['q' => $namePart2], [$reservation2->id]);
        $this->assertSearchIds(['q' => $descriptionPart2], [$reservation2->id]);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByQueryContactsAndCode(): void
    {
        $firstNamePart1 = 'f_unique';
        $firstNamePart2 = 'f_also_unique';

        $lastNamePart1 = 'l_unique';
        $lastNamePart2 = 'l_also_unique';

        $codePart1 = 'custom_code';
        $codePart2 = 'unique_code';

        $emailPart1 = 'f_anywhere_not_used_email';
        $emailPart2 = 's_not_used_anywhere_email';

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail($emailPart1)));
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail($emailPart2)));

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $reservation1 = $this->makeReservation($voucher1, $product1, [
            'first_name' => "$firstNamePart1 name",
            'last_name' => "$lastNamePart1 last",
        ]);

        $reservation1->update(['code' => "$codePart1 code"]);

        $reservation2 = $this->makeReservation($voucher2, $product2, [
            'first_name' => "$firstNamePart2 name",
            'last_name' => "$lastNamePart2 last",
        ]);

        $reservation2->update(['code' => "$codePart2 code"]);

        // assert by first name
        $this->assertSearchIds(['q' => $firstNamePart1], [$reservation1->id]);
        $this->assertSearchIds(['q' => $firstNamePart2], [$reservation2->id]);

        // assert by last name
        $this->assertSearchIds(['q' => $lastNamePart1], [$reservation1->id]);
        $this->assertSearchIds(['q' => $lastNamePart2], [$reservation2->id]);

        // assert by code
        $this->assertSearchIds(['q' => $codePart1], [$reservation1->id]);
        $this->assertSearchIds(['q' => $codePart2], [$reservation2->id]);

        // assert by identity email
        $this->assertSearchIds(['q' => $emailPart1], [$reservation1->id]);
        $this->assertSearchIds(['q' => $emailPart2], [$reservation2->id]);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByQueryAsProvider(): void
    {
        $invoicePart1 = 'unique_invoice_part1';
        $invoicePart2 = 'other_invoice_part1';

        $notePart1 = 'short_note_part';
        $notePart2 = 'long_note_part';

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation1->update(['invoice_number' => "$invoicePart1 invoice_number"]);
        $reservation1->addNote("$notePart1 note");

        $reservation2 = $this->makeReservation($voucher2, $product2);
        $reservation2->update(['invoice_number' => "$invoicePart2 invoice_number"]);
        $reservation2->addNote("$notePart2 note");

        // assert by note part
        $this->assertSearchIds([
            'q' => $notePart1,
            'q_type' => 'provider',
        ], [$reservation1->id]);

        $this->assertSearchIds([
            'q' => $notePart2,
            'q_type' => 'provider',
        ], [$reservation2->id]);

        // assert by invoice number
        $this->assertSearchIds([
            'q' => $invoicePart1,
            'q_type' => 'provider',
        ], [$reservation1->id]);

        $this->assertSearchIds([
            'q' => $invoicePart2,
            'q_type' => 'provider',
        ], [$reservation2->id]);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByQueryAsProviderAndCustomFields(): void
    {
        $fieldPart1 = 'unique_field_part1';
        $fieldPart2 = 'other_field_part1';

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());

        $field = $provider->reservation_fields()->create([
            'label' => 'organization custom field text',
            'type' => ReservationField::TYPE_TEXT,
            'description' => 'organization custom field text description',
            'required' => true,
            'fillable_by' => ReservationField::FILLABLE_BY_REQUESTER,
            'order' => 0,
        ]);

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($provider, [$fund]);

        $product1->update([
            'reservation_fields_enabled' => true,
            'reservation_fields_config' => $product1::CUSTOM_RESERVATION_FIELDS_GLOBAL,
        ]);

        $product2 = $this->createProductForReservation($provider, [$fund]);

        $product2->update([
            'reservation_fields_enabled' => true,
            'reservation_fields_config' => $product2::CUSTOM_RESERVATION_FIELDS_GLOBAL,
        ]);

        $reservation1 = $this->makeReservation($voucher1, $product1, [
            'custom_fields' => [$field->id => "$fieldPart1 custom field"],
        ]);

        $reservation2 = $this->makeReservation($voucher2, $product2, [
            'custom_fields' => [$field->id => "$fieldPart2 custom field"],
        ]);

        $this->assertSearchIds([
            'q' => $fieldPart1,
            'q_type' => 'provider',
        ], [$reservation1->id]);

        $this->assertSearchIds([
            'q' => $fieldPart2,
            'q_type' => 'provider',
        ], [$reservation2->id]);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByProductId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        $this->assertSearchIds(['product_id' => $product1->id], [$reservation1->id]);
        $this->assertSearchIds(['product_id' => $product2->id], [$reservation2->id]);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByFundId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund1, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund2, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund1]);
        $product2 = $this->createProductForReservation($organization, [$fund2]);

        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        $this->assertSearchIds(['fund_id' => $fund1->id], [$reservation1->id]);
        $this->assertSearchIds(['fund_id' => $fund2->id], [$reservation2->id]);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByOrganizationId(): void
    {
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity());

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($provider1, [$fund]);
        $product2 = $this->createProductForReservation($provider2, [$fund]);

        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        $this->assertSearchIds(['organization_id' => $provider1->id], [$reservation1->id]);
        $this->assertSearchIds(['organization_id' => $provider2->id], [$reservation2->id]);
    }

    /**
     * @throws Exception
     * @return void
     */
    public function testFiltersByVoucherId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund1, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund2, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund1]);
        $product2 = $this->createProductForReservation($organization, [$fund2]);

        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        $this->assertSearchIds(['voucher_id' => $voucher1->id], [$reservation1->id]);
        $this->assertSearchIds(['voucher_id' => $voucher2->id], [$reservation2->id]);
    }

    /**
     * @return void
     */
    public function testFilterByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation1->created_at = Carbon::now()->addDays(2);
        $reservation1->save();

        $reservation2 = $this->makeReservation($voucher2, $product2);
        $reservation2->created_at = Carbon::now()->addDays(10);
        $reservation2->save();

        $this->assertSearchIds([
            'from' => Carbon::now()->addDays()->format('Y-m-d'),
            'to' => Carbon::now()->addDays(5)->format('Y-m-d'),
        ], [$reservation1->id]);

        $this->assertSearchIds([
            'from' => Carbon::now()->addDays(5)->format('Y-m-d'),
            'to' => Carbon::now()->addDays(12)->format('Y-m-d'),
        ], [$reservation2->id]);

        $this->assertSearchIds([
            'from' => Carbon::now()->addDays(5)->format('Y-m-d'),
        ], [$reservation2->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'to' => Carbon::now()->addDays(5)->format('Y-m-d'),
        ], [$reservation1->id]);

        $this->assertSearchIds([
            'from' => Carbon::now()->addDays()->format('Y-m-d'),
            'to' => Carbon::now()->addDays(12)->format('Y-m-d'),
        ], [$reservation1->id, $reservation2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFilterByBaseState(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        // make pending reservations
        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        // assert two pending reservations
        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation1::STATE_PENDING,
        ], [$reservation1->id, $reservation2->id]);

        // make second reservation accepted
        DB::beginTransaction();
        $reservation2->acceptProvider();

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation1::STATE_PENDING,
        ], [$reservation1->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation2::STATE_ACCEPTED,
        ], [$reservation2->id]);

        DB::rollBack();

        // make second reservation rejected
        DB::beginTransaction();
        $reservation2->refresh();
        $reservation2->rejectOrCancelProvider();

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation1::STATE_PENDING,
        ], [$reservation1->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation2::STATE_REJECTED,
        ], [$reservation2->id]);

        DB::rollBack();

        // make second reservation canceled by provider
        DB::beginTransaction();
        $reservation2->refresh();
        $reservation2->acceptProvider();
        $reservation2->refresh();
        $reservation2->rejectOrCancelProvider();

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation1::STATE_PENDING,
        ], [$reservation1->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation2::STATE_CANCELED_BY_PROVIDER,
        ], [$reservation2->id]);

        DB::rollBack();

        // make second reservation canceled by client
        DB::beginTransaction();
        $reservation2->refresh();
        $reservation2->cancelByClient();

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation1::STATE_PENDING,
        ], [$reservation1->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation2::STATE_CANCELED_BY_CLIENT,
        ], [$reservation2->id]);

        DB::rollBack();

        // make second reservation canceled by sponsor
        DB::beginTransaction();
        $reservation2->refresh();
        $reservation2->cancelBySponsor();

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation1::STATE_PENDING,
        ], [$reservation1->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation2::STATE_CANCELED_BY_SPONSOR,
        ], [$reservation2->id]);

        DB::rollBack();
    }

    /**
     * @return void
     */
    public function testFilterByExpired(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        // make pending reservations
        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        // assert two pending reservations
        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation1::STATE_PENDING,
        ], [$reservation1->id, $reservation2->id]);

        // make first voucher expired
        $voucher1->update(['expire_at' => now()->subDay()]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation1::STATE_PENDING,
        ], [$reservation2->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => 'expired',
        ], [$reservation1->id]);

        // make fund expired and assert all reservations are expired
        $fund->update(['end_date' => now()->subDay()]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation1::STATE_PENDING,
        ], []);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => 'expired',
        ], [$reservation1->id, $reservation2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFilterByStateRelatedToExtraPayment(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        // make pending reservations
        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        // assert two pending reservations
        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation1::STATE_PENDING,
        ], [$reservation1->id, $reservation2->id]);

        // make first reservation state waiting
        DB::beginTransaction();
        $reservation1->update(['state' => $reservation1::STATE_WAITING]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation1::STATE_WAITING,
        ], [$reservation1->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation2::STATE_PENDING,
        ], [$reservation2->id]);

        DB::rollBack();

        // make first reservation as payment failed
        DB::beginTransaction();
        $reservation1->cancelByState($reservation1::STATE_CANCELED_PAYMENT_FAILED);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation1::STATE_CANCELED_PAYMENT_FAILED,
        ], [$reservation1->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation2::STATE_PENDING,
        ], [$reservation2->id]);

        DB::rollBack();

        // make first reservation as payment expired
        DB::beginTransaction();
        $reservation1->cancelByState($reservation1::STATE_CANCELED_PAYMENT_EXPIRED);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation1::STATE_CANCELED_PAYMENT_EXPIRED,
        ], [$reservation1->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation2::STATE_PENDING,
        ], [$reservation2->id]);

        DB::rollBack();

        // make first reservation as payment canceled
        DB::beginTransaction();
        $reservation1->cancelByState($reservation1::STATE_CANCELED_PAYMENT_CANCELED);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation1::STATE_CANCELED_PAYMENT_CANCELED,
        ], [$reservation1->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'state' => $reservation2::STATE_PENDING,
        ], [$reservation2->id]);

        DB::rollBack();
    }

    /**
     * @return void
     */
    public function testFilterByArchived(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        // make pending reservations
        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        // assert two pending reservations
        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'archived' => false,
        ], [$reservation1->id, $reservation2->id]);

        $reservation1->archive($organization->employees()->first());

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'archived' => true,
        ], [$reservation1->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'archived' => false,
        ], [$reservation2->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFilterByArchivedForWebshop(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        // make pending reservations
        $reservation1 = $this->makeReservation($voucher1, $product1);
        $reservation2 = $this->makeReservation($voucher2, $product2);

        // assert two pending reservations
        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'archived' => false,
            'is_webshop' => true,
        ], [$reservation1->id, $reservation2->id]);

        // cancel first reservation and assert it present as archived for webshop
        $reservation1->cancelByClient();

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'archived' => true,
            'is_webshop' => true,
        ], [$reservation1->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'archived' => false,
            'is_webshop' => true,
        ], [$reservation2->id]);

        // make second reservation pending and expired
        $voucher2->update(['expire_at' => now()->subDay()]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'archived' => true,
            'is_webshop' => true,
        ], [$reservation1->id, $reservation2->id]);

        $this->assertSearchIds([
            'organization_id' => $organization->id,
            'archived' => false,
            'is_webshop' => true,
        ], []);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $reservationA = $this->makeReservation($voucher1, $product1);

        Carbon::setTestNow(now()->addDays(5));
        $reservationB = $this->makeReservation($voucher2, $product2);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$reservationA->id, $reservationB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$reservationB->id, $reservationA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByCode(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $reservationA = $this->makeReservation($voucher1, $product1);
        $reservationA->update(['code' => 'A code']);

        $reservationB = $this->makeReservation($voucher2, $product2);
        $reservationB->update(['code' => 'B code']);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'code',
            'order_dir' => 'asc',
        ], [$reservationA->id, $reservationB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'code',
            'order_dir' => 'desc',
        ], [$reservationB->id, $reservationA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByCustomer(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $reservationA = $this->makeReservation($voucher1, $product1, [
            'first_name' => 'A first name',
        ]);

        $reservationB = $this->makeReservation($voucher2, $product2, [
            'first_name' => 'B first name',
        ]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'customer',
            'order_dir' => 'asc',
        ], [$reservationA->id, $reservationB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'customer',
            'order_dir' => 'desc',
        ], [$reservationB->id, $reservationA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByProduct(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product1->update(['name' => 'A product name']);

        $product2 = $this->createProductForReservation($organization, [$fund]);
        $product2->update(['name' => 'B product name']);

        $reservationA = $this->makeReservation($voucher1, $product1);
        $reservationB = $this->makeReservation($voucher2, $product2);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'product',
            'order_dir' => 'asc',
        ], [$reservationA->id, $reservationB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'product',
            'order_dir' => 'desc',
        ], [$reservationB->id, $reservationA->id]);
    }

    /**
     * @return void
     */
    public function testOrdersByProvider(): void
    {
        $provider1 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => 'A provider name']);
        $provider2 = $this->makeTestProviderOrganization($this->makeIdentity(), ['name' => 'B provider name']);

        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($provider1, [$fund]);
        $product2 = $this->createProductForReservation($provider2, [$fund]);

        $reservationA = $this->makeReservation($voucher1, $product1);
        $reservationB = $this->makeReservation($voucher2, $product2);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'provider',
            'order_dir' => 'asc',
        ], [$reservationA->id, $reservationB->id]);

        $this->assertSearchOrder([
            'fund_id' => $fund->id,
            'order_by' => 'provider',
            'order_dir' => 'desc',
        ], [$reservationB->id, $reservationA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByState(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $reservationA = $this->makeReservation($voucher1, $product1);
        $reservationB = $this->makeReservation($voucher2, $product2)->acceptProvider();

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'state',
            'order_dir' => 'asc',
        ], [$reservationA->id, $reservationB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'state',
            'order_dir' => 'desc',
        ], [$reservationB->id, $reservationA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByPrice(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund], 5);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $reservationA = $this->makeReservation($voucher1, $product1);
        $reservationB = $this->makeReservation($voucher2, $product2);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'price',
            'order_dir' => 'asc',
        ], [$reservationA->id, $reservationB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'price',
            'order_dir' => 'desc',
        ], [$reservationB->id, $reservationA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByTransactionId(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $reservationA = $this->makeReservation($voucher1, $product1)->acceptProvider();
        $reservationB = $this->makeReservation($voucher2, $product2)->acceptProvider();

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'transaction_id',
            'order_dir' => 'asc',
        ], [$reservationA->id, $reservationB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'transaction_id',
            'order_dir' => 'desc',
        ], [$reservationB->id, $reservationA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByTransactionState(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $reservationA = $this->makeReservation($voucher1, $product1)->acceptProvider();

        $reservationB = $this->makeReservation($voucher2, $product2)->acceptProvider();
        $reservationB->voucher_transaction->setPaid(null, null);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'transaction_state',
            'order_dir' => 'asc',
        ], [$reservationA->id, $reservationB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'transaction_state',
            'order_dir' => 'desc',
        ], [$reservationB->id, $reservationA->id]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByAmountExtra(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product1 = $this->createProductForReservation($organization, [$fund]);
        $product2 = $this->createProductForReservation($organization, [$fund]);

        $reservationA = $this->makeReservation($voucher1, $product1);
        $reservationA->update(['amount_extra' => 5]);

        $reservationB = $this->makeReservation($voucher2, $product2);
        $reservationB->update(['amount_extra' => 10]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'amount_extra',
            'order_dir' => 'asc',
        ], [$reservationA->id, $reservationB->id]);

        $this->assertSearchOrder([
            'organization_id' => $organization->id,
            'order_by' => 'amount_extra',
            'order_dir' => 'desc',
        ], [$reservationB->id, $reservationA->id]);
    }

    /**
     * @param array $filters
     * @return ProductReservationsSearch
     */
    private function makeSearch(array $filters): ProductReservationsSearch
    {
        return new ProductReservationsSearch($filters, ProductReservation::query());
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @return void
     */
    private function assertSearchIds(array $filters, array $expectedIds): void
    {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters);
        $actual = collect($search->query()->pluck('id')->toArray())->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @return void
     */
    private function assertSearchOrder(array $filters, array $expectedIds): void
    {
        $search = $this->makeSearch($filters);
        $actual = $search->query()->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
