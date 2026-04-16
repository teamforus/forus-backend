<?php

namespace Tests\Unit\Searches\Sponsor;

use App\Models\Data\BankAccount;
use App\Models\Fund;
use App\Models\Organization;
use App\Models\RecordType;
use App\Models\Voucher;
use App\Models\VoucherRelation;
use App\Models\VoucherTransactionBulk;
use App\Searches\VouchersSearch;
use App\Traits\DoesTesting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestBankConnections;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestPhysicalCardTypes;
use Tests\Traits\MakesTestVouchers;
use Tests\Traits\MakesVoucherTransaction;
use Tests\Unit\Searches\SearchTestCase;
use Throwable;

class VouchersSearchTest extends SearchTestCase
{
    use DoesTesting;
    use MakesTestFunds;
    use MakesTestVouchers;
    use MakesTestOrganizations;
    use MakesVoucherTransaction;
    use MakesTestBankConnections;
    use MakesProductReservations;
    use MakesTestPhysicalCardTypes;

    /**
     * @return void
     */
    public function testQueryBuilds(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $search = new VouchersSearch([
            'type' => 'all',
            'source' => 'all',
        ], Voucher::query());

        $this->assertQueryBuilds($search->searchSponsor($organization));
    }

    /**
     * @return void
     */
    public function testFiltersByQuery()
    {
        $emailPart1 = 'unique';
        $emailPart2 = 'other';

        $activationCodePart1 = 'first';
        $activationCodePart2 = 'second';

        $bsnPart1 = '11111';
        $bsnPart2 = '22222';

        $relationBsnPart1 = '55555';
        $relationBsnPart2 = '66666';

        $clientUidPart1 = 'longer';
        $clientUidPart2 = 'shorter';

        $notePart1 = 'interesting';
        $notePart2 = 'sad';

        $cardPart1 = '33333';
        $cardPart2 = '44444';

        $recordPart1 = 'qwerty';
        $recordPart2 = 'something';

        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        // make identity
        $identity1 = $this->makeIdentity($this->makeUniqueEmail($emailPart1), bsn: "{$bsnPart1}8888");
        $identity2 = $this->makeIdentity($this->makeUniqueEmail($emailPart2), bsn: "{$bsnPart2}8888");

        // make vouchers
        $voucher1 = $this->makeTestVoucher($fund1, $identity1, [
            'note' => "$notePart1 note",
            'client_uid' => "{$clientUidPart1}_uid",
            'activation_code' => "{$activationCodePart1}_code",
        ]);

        $voucher2 = $this->makeTestVoucher($fund2, $identity2, [
            'note' => "$notePart2",
            'client_uid' => "{$clientUidPart2}_uid",
            'activation_code' => "{$activationCodePart2}_code",
        ]);

        // make record types and append test records
        $recordType1 = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'test_string_a');
        $recordType2 = $this->makeRecordType($organization, RecordType::TYPE_STRING, 'test_string_b');

        $voucher1->appendRecord($recordType1->key, "$recordPart1 record name");
        $voucher2->appendRecord($recordType2->key, "$recordPart2 record name");

        //add bsn relation values
        $voucher1->setBsnRelation("{$relationBsnPart1}8888", VoucherRelation::REPORT_TYPE_USER);
        $voucher2->setBsnRelation("{$relationBsnPart2}8888", VoucherRelation::REPORT_TYPE_USER);

        // assign physical cards to vouchers
        $voucher1->addPhysicalCard("{$cardPart1}00088888888", $this->makeTestPhysicalCardType($organization));
        $voucher2->addPhysicalCard("{$cardPart2}99988888888", $this->makeTestPhysicalCardType($organization));

        // assert search by identity email
        $this->assertSearchIds(['q' => $emailPart1], [$voucher1->id], $organization);
        $this->assertSearchIds(['q' => $emailPart2], [$voucher2->id], $organization);

        // assert search by identity bsn
        $this->assertSearchIds(['q' => $bsnPart1], [$voucher1->id], $organization);
        $this->assertSearchIds(['q' => $bsnPart2], [$voucher2->id], $organization);

        // assert search by relation bsn
        $this->assertSearchIds(['q' => $relationBsnPart1], [$voucher1->id], $organization);
        $this->assertSearchIds(['q' => $relationBsnPart2], [$voucher2->id], $organization);

        // assert search by activation code
        $this->assertSearchIds(['q' => $activationCodePart1], [$voucher1->id], $organization);
        $this->assertSearchIds(['q' => $activationCodePart2], [$voucher2->id], $organization);

        // assert search by client_uid
        $this->assertSearchIds(['q' => $clientUidPart1], [$voucher1->id], $organization);
        $this->assertSearchIds(['q' => $clientUidPart2], [$voucher2->id], $organization);

        // assert search by note
        $this->assertSearchIds(['q' => $notePart1], [$voucher1->id], $organization);
        $this->assertSearchIds(['q' => $notePart2], [$voucher2->id], $organization);

        // assert search by physical card
        $this->assertSearchIds(['q' => $cardPart1], [$voucher1->id], $organization);
        $this->assertSearchIds(['q' => $cardPart2], [$voucher2->id], $organization);

        // assert search by record
        $this->assertSearchIds(['q' => $recordPart1], [$voucher1->id], $organization);
        $this->assertSearchIds(['q' => $recordPart2], [$voucher2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByIdentity()
    {
        $email1 = $this->makeUniqueEmail();
        $email2 = $this->makeUniqueEmail();

        $bsn1 = Str::random(10);
        $bsn2 = Str::random(10);

        $clientUid1 = Str::random(10);
        $clientUid2 = Str::random(10);

        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        // make identity
        $identity1 = $this->makeIdentity($email1, bsn: $bsn1);
        $identity2 = $this->makeIdentity($email2, bsn: $bsn2);

        // make vouchers
        $voucher1 = $this->makeTestVoucher($fund1, $identity1, ['client_uid' => $clientUid1]);
        $voucher2 = $this->makeTestVoucher($fund2, $identity2, ['client_uid' => $clientUid2]);

        // assert search by identity email
        $this->assertSearchIds(['email' => $email1], [$voucher1->id], $organization);
        $this->assertSearchIds(['email' => $email2], [$voucher2->id], $organization);

        // assert search by identity bsn
        $this->assertSearchIds(['bsn' => $bsn1], [$voucher1->id], $organization);
        $this->assertSearchIds(['bsn' => $bsn2], [$voucher2->id], $organization);

        // assert search by client_uid
        $this->assertSearchIds(['client_uid' => $clientUid1], [$voucher1->id], $organization);
        $this->assertSearchIds(['client_uid' => $clientUid2], [$voucher2->id], $organization);

        // assert search by identity_id
        $this->assertSearchIds(['identity_id' => $identity1->id], [$voucher1->id], $organization);
        $this->assertSearchIds(['identity_id' => $identity2->id], [$voucher2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByImplementationIdOrImplementationKey(): void
    {
        $organization1 = $this->makeTestOrganization($this->makeIdentity());
        $organization2 = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization1);
        $fund2 = $this->makeTestFund($organization2);

        $voucher1 = $this->makeTestVoucher($fund1, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund2, identity: $this->makeIdentity());

        $this->assertSearchIds(['implementation_id' => $fund1->getImplementation()->id], [$voucher1->id], $organization1);
        $this->assertSearchIds(['implementation_id' => $fund2->getImplementation()->id], [$voucher2->id], $organization2);

        $this->assertSearchIds(['implementation_key' => $fund1->getImplementation()->key], [$voucher1->id], $organization1);
        $this->assertSearchIds(['implementation_key' => $fund2->getImplementation()->key], [$voucher2->id], $organization2);
    }

    /**
     * @return void
     */
    public function testFiltersByVoucherType(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $fund->makePayout(
            identity: $this->makeIdentity(),
            amount: 100,
            employee: $organization->employees()->first(),
            bankAccount: new BankAccount($this->faker()->iban(), $this->faker()->name()),
        );

        $this->assertSearchIds([], [$voucher->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByFund(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund1, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund2, identity: $this->makeIdentity());

        $this->assertSearchIds([], [$voucher1->id, $voucher2->id], $organization);

        $this->assertSearchIds([], [$voucher1->id], $organization, $fund1);
        $this->assertSearchIds([], [$voucher2->id], $organization, $fund2);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByExpired(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $activeVoucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $expiredVoucher = $this->makeTestVoucher($fund, $this->makeIdentity(), ['expire_at' => Carbon::now()->subDay()]);

        $this->assertSearchIds(['expired' => false], [$activeVoucher->id], $organization);
        $this->assertSearchIds(['expired' => true], [$expiredVoucher->id], $organization);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByState(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $activeVoucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $deactivatedVoucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity())->deactivate();
        $expiredVoucher = $this->makeTestVoucher($fund, $this->makeIdentity(), ['expire_at' => Carbon::now()->subDay()]);

        $pendingVoucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $pendingVoucher->setPending();

        $this->assertSearchIds(['state' => Voucher::STATE_ACTIVE], [$activeVoucher->id], $organization);
        $this->assertSearchIds(['state' => Voucher::STATE_PENDING], [$pendingVoucher->id], $organization);
        $this->assertSearchIds(['state' => Voucher::STATE_DEACTIVATED], [$deactivatedVoucher->id], $organization);
        $this->assertSearchIds(['state' => 'expired'], [$expiredVoucher->id], $organization);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByGrantedOrUnassigned(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $assignedVoucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $notAssignedVoucher = $this->makeTestVoucher($fund);

        $this->assertSearchIds(['granted' => true], [$assignedVoucher->id], $organization);
        $this->assertSearchIds(['granted' => false], [$notAssignedVoucher->id], $organization);

        $this->assertSearchIds(['unassigned' => true], [$notAssignedVoucher->id], $organization);
        $this->assertSearchIds(['unassigned' => false], [$assignedVoucher->id], $organization);
    }

    /**
     * @return void
     */
    public function testFilterByCreatedAt(): void
    {
        $now = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        Carbon::setTestNow($now->clone()->addDays(7));
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $this->assertSearchIds([
            'from' => $now->clone()->subDays()->format('Y-m-d'),
            'to' => $now->clone()->addDays(5)->format('Y-m-d'),
        ], [$voucher1->id], $organization);

        $this->assertSearchIds([
            'from' => $now->clone()->addDays(5)->format('Y-m-d'),
            'to' => $now->clone()->addDays(8)->format('Y-m-d'),
        ], [$voucher2->id], $organization);

        $this->assertSearchIds([
            'from' => $now->clone()->addDays(5)->format('Y-m-d'),
        ], [$voucher2->id], $organization);

        $this->assertSearchIds([
            'to' => $now->clone()->addDays(5)->format('Y-m-d'),
        ], [$voucher1->id], $organization);

        $this->assertSearchIds([
            'from' => $now->clone()->subDays(2)->format('Y-m-d'),
            'to' => $now->clone()->addDays(8)->format('Y-m-d'),
        ], [$voucher1->id, $voucher2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByCountPerIdentity(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);
        $fundOther = $this->makeTestFund($organization);

        $identity1 = $this->makeIdentity();
        $identity2 = $this->makeIdentity();

        $this->makeTestVoucher($fundOther, identity: $identity1);
        $voucherIdentityHas2 = $this->makeTestVoucher($fund, identity: $identity1);
        $voucherIdentityHas1 = $this->makeTestVoucher($fund, identity: $identity2);

        $this->assertSearchIds([
            'count_per_identity_min' => 2,
        ], [$voucherIdentityHas2->id], $organization, $fund);

        $this->assertSearchIds([
            'count_per_identity_min' => 1,
        ], [$voucherIdentityHas1->id, $voucherIdentityHas2->id], $organization, $fund);

        $this->assertSearchIds([
            'count_per_identity_max' => 2,
        ], [$voucherIdentityHas1->id, $voucherIdentityHas2->id], $organization, $fund);

        $this->assertSearchIds([
            'count_per_identity_max' => 1,
        ], [$voucherIdentityHas1->id], $organization, $fund);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByHasPayout(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());

        $this->makeTestImplementation($organization);
        $this->makeBankConnection($organization);

        $fund = $this->makeTestFund($organization);

        $voucherHasTransaction = $this->makeTransactions($fund, 1)[0]->voucher;
        $voucherDoesntHaveTransaction = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $bulkIds = VoucherTransactionBulk::buildBulksForOrganization($organization);
        $this->assertCount(1, $bulkIds);
        $bulk = VoucherTransactionBulk::whereIn('id', $bulkIds)->first();
        $bulk->setAcceptedBNG(null, false);

        $this->assertSearchIds([
            'has_payouts' => true,
        ], [$voucherHasTransaction->id], $organization);

        $this->assertSearchIds([], [$voucherHasTransaction->id, $voucherDoesntHaveTransaction->id], $organization);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOrdersByInUse(): void
    {
        $now = Carbon::now();
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund1 = $this->makeTestFund($organization);
        $fund2 = $this->makeTestFund($organization);

        $voucherHasReservation1 = $this->makeTestVoucher($fund1, identity: $this->makeIdentity());
        $voucherHasReservation2 = $this->makeTestVoucher($fund2, identity: $this->makeIdentity());
        $voucherNotInUse = $this->makeTestVoucher($fund1, identity: $this->makeIdentity());

        // make provider and product
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->createProductForReservation($provider, [$fund1, $fund2]);

        // move test date and create reservation
        Carbon::setTestNow($now->clone()->addDays(5));
        $reservation = $voucherHasReservation1->reserveProduct($product);
        $reservation->acceptProvider();

        // move test date and create another reservation
        Carbon::setTestNow($now->clone()->addDays(10));
        $reservation = $voucherHasReservation2->reserveProduct($product);
        $reservation->acceptProvider();

        $this->assertSearchIds([
            'in_use' => true,
        ], [$voucherHasReservation1->id, $voucherHasReservation2->id], $organization);

        $this->assertSearchIds([
            'in_use' => false,
        ], [$voucherNotInUse->id], $organization);

        $this->assertSearchIds([
            'in_use_from' => $now->clone()->addDays(4)->format('Y-m-d'),
        ], [$voucherHasReservation1->id, $voucherHasReservation2->id], $organization);

        $this->assertSearchIds([
            'in_use_from' => $now->clone()->addDays(6)->format('Y-m-d'),
        ], [$voucherHasReservation2->id], $organization);

        $this->assertSearchIds([
            'in_use_to' => $now->clone()->addDays(6)->format('Y-m-d'),
        ], [$voucherHasReservation1->id], $organization);

        $this->assertSearchIds([
            'in_use_to' => $now->clone()->addDays(12)->format('Y-m-d'),
        ], [$voucherHasReservation1->id, $voucherHasReservation2->id], $organization);

        $this->assertSearchIds([
            'in_use_from' => $now->clone()->addDays(6)->format('Y-m-d'),
            'in_use_to' => $now->clone()->addDays(12)->format('Y-m-d'),
        ], [$voucherHasReservation2->id], $organization);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testFiltersByAmount(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity(), amount: 100);
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity(), amount: 50);

        $this->assertSearchIds([
            'amount_min' => 10,
        ], [$voucher1->id, $voucher2->id], $organization);

        $this->assertSearchIds([
            'amount_min' => 70,
        ], [$voucher1->id], $organization);

        $this->assertSearchIds([
            'amount_max' => 70,
        ], [$voucher2->id], $organization);

        $this->assertSearchIds([
            'amount_min' => 40,
            'amount_max' => 60,
        ], [$voucher2->id], $organization);

        $this->assertSearchIds([
            'amount_min' => 10,
            'amount_max' => 120,
        ], [$voucher1->id, $voucher2->id], $organization);

        // make provider and product
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product1 = $this->createProductForReservation($provider, [$fund], 70);
        $product2 = $this->createProductForReservation($provider, [$fund], 5);

        $reservation = $voucher1->reserveProduct($product1);
        $reservation->acceptProvider();

        $reservation = $voucher2->reserveProduct($product2);
        $reservation->acceptProvider();

        $this->assertSearchIds([
            'amount_available_min' => 50,
        ], [], $organization);

        $this->assertSearchIds([
            'amount_available_min' => 10,
        ], [$voucher1->id, $voucher2->id], $organization);

        $this->assertSearchIds([
            'amount_available_min' => 35,
        ], [$voucher2->id], $organization);

        $this->assertSearchIds([
            'amount_available_max' => 50,
        ], [$voucher1->id, $voucher2->id], $organization);

        $this->assertSearchIds([
            'amount_available_min' => 10,
            'amount_available_max' => 30,
        ], [$voucher1->id], $organization);

        $this->assertSearchIds([
            'amount_available_min' => 35,
            'amount_available_max' => 50,
        ], [$voucher2->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByTypeAndSource(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product = $this->makeTestProviderWithProducts(1)[0];
        $this->addProductToFund($fund, $product, false);

        $productVoucher = $this->makeTestProductVoucher($fund, $this->makeIdentity(), [
            'employee_id' => $organization->employees()->first()->id,
        ], $product->id);

        $this->assertSearchIds([
            'type' => 'fund_voucher',
        ], [$voucher1->id, $voucher2->id], $organization);

        $this->assertSearchIds([
            'type' => 'product_voucher',
        ], [$productVoucher->id], $organization);

        $this->assertSearchIds([
            'source' => 'user',
        ], [$voucher1->id, $voucher2->id], $organization);

        $this->assertSearchIds([
            'source' => 'employee',
        ], [$productVoucher->id], $organization);
    }

    /**
     * @return void
     */
    public function testFiltersByVisibleToSponsor(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucher1 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $voucher2 = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $product = $this->makeTestProviderWithProducts(1)[0];
        $this->addProductToFund($fund, $product, false);

        $productVoucher = $this->makeTestProductVoucher($fund, $this->makeIdentity(), [
            'employee_id' => $organization->employees()->first()->id,
        ], $product->id);

        // make provider and product and reservation and buy product voucher - it will have parent_id
        // and must not be visible
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->createProductForReservation($provider, [$fund]);
        $voucher1->buyProductVoucher($product);

        $this->assertSearchIds([], [$voucher1->id, $voucher2->id, $productVoucher->id], $organization);
    }

    /**
     * @return void
     */
    public function testOrdersByCreatedAt(): void
    {
        $organization = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($organization);

        $voucherA = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        Carbon::setTestNow(Carbon::now()->addDays(5));
        $voucherB = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $this->assertSearchOrder([
            'implementation_id' => $fund->getImplementation()->id,
            'order_by' => 'created_at',
            'order_dir' => 'asc',
        ], [$voucherA->id, $voucherB->id], $organization);

        $this->assertSearchOrder([
            'implementation_id' => $fund->getImplementation()->id,
            'order_by' => 'created_at',
            'order_dir' => 'desc',
        ], [$voucherB->id, $voucherA->id], $organization);
    }

    /**
     * @param array $filters
     * @return VouchersSearch
     */
    private function makeSearch(array $filters): VouchersSearch
    {
        return new VouchersSearch([
            'type' => 'all',
            'source' => 'all',
            ...$filters,
        ], Voucher::query());
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Organization $organization
     * @param Fund|null $fund
     * @return void
     */
    private function assertSearchIds(
        array $filters,
        array $expectedIds,
        Organization $organization,
        Fund $fund = null,
    ): void {
        $expected = collect($expectedIds)->sort()->values()->toArray();
        $search = $this->makeSearch($filters);

        $actual = collect($search->searchSponsor($organization, $fund)->pluck('id')->toArray())
            ->sort()->values()->toArray();

        $this->assertSame($expected, $actual);
    }

    /**
     * @param array $filters
     * @param array $expectedIds
     * @param Organization $organization
     * @return void
     */
    private function assertSearchOrder(array $filters, array $expectedIds, Organization $organization): void
    {
        $search = $this->makeSearch($filters);
        $actual = $search->searchSponsor($organization)->pluck('id')->toArray();

        $this->assertSame($expectedIds, $actual);
    }
}
