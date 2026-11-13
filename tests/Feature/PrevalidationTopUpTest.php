<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundFormula;
use App\Models\Prevalidation;
use App\Models\PrevalidationRecord;
use App\Models\RecordType;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class PrevalidationTopUpTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationReuploadCreditsOnlyPositiveDifference(): void
    {
        ['fund' => $fund, 'prevalidation' => $prevalidation, 'voucher' => $voucher] = $this->makeTopUpFixture();
        $data = $this->topUpUploadData($prevalidation);

        $this
            ->makeStorePrevalidationBatchRequest($fund->organization, $fund, [$data], topUp: [
                ['key' => $data['uid'], 'voucher_id' => $voucher->id],
            ])
            ->assertSuccessful()
            ->assertJsonPath('data.0.id', $prevalidation->id);

        $this->assertEquals(100, $voucher->refresh()->amount);
        $this->assertEquals(150, $voucher->amount_total);

        $transaction = $voucher->top_up_transactions()->sole();

        $this->assertEquals(50, $transaction->amount);
        $this->assertTopUpRejected($fund, $voucher, $data);

        $this->assertEquals(150, $voucher->refresh()->amount_total);
        $this->assertSame([$transaction->id], $voucher->top_up_transactions()->pluck('id')->all());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPrevalidationWithoutMatchingBudgetFormulaDoesNotTopUp(): void
    {
        ['fund' => $fund, 'prevalidation' => $prevalidation, 'voucher' => $voucher] = $this->makeTopUpFixture();

        $fund->fund_formulas()->sole()->conditions()->create([
            'record_type_id' => RecordType::findByKey('test_number')->id, 'operator' => '>', 'value' => '10',
        ]);

        $records = $prevalidation->records()->orderBy('id')->get()->toArray();
        $recordsHash = $prevalidation->records_hash;

        $this->assertTopUpRejected($fund, $voucher, $this->topUpUploadData($prevalidation));

        $this->assertEquals(100, $voucher->refresh()->amount_total);
        $this->assertFalse($voucher->top_up_transactions()->exists());
        $this->assertSame($records, $prevalidation->records()->orderBy('id')->get()->toArray());
        $this->assertSame($recordsHash, $prevalidation->refresh()->records_hash);
    }

    /**
     * @throws Throwable
     * @return array{fund: Fund, prevalidation: Prevalidation, voucher: Voucher}
     */
    private function makeTopUpFixture(): array
    {
        $this->travelTo('2026-11-13 12:00:00');
        $organization = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $fund = $this->makeTestFund($organization);

        $this->addTestCriteriaToFund($fund);
        $fund->criteria()->where('record_type_key', 'test_number')->update(['value' => '0', 'min' => '0']);
        $fund->fund_formulas()->delete();

        $fund->fund_formulas()->create([
            'type' => FundFormula::TYPE_MULTIPLY, 'amount' => 50, 'record_type_key' => 'test_number',
        ]);

        $prevalidation = $this->makePrevalidationForTestCriteria($organization, $fund->refresh());

        $prevalidation->records()->where('record_type_id', RecordType::findByKey('test_number')->id)->update(['value' => '2']);
        $prevalidation->updateHashes();

        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $prevalidation->refresh()->assignToIdentity($identity);
        $voucher = $fund->refresh()->makeVoucher(identity: $identity);

        $this->assertNotNull($voucher);
        $this->assertEquals(100, $voucher->amount_total);
        $this->assertSame(Prevalidation::STATE_USED, $prevalidation->refresh()->state);

        return compact('fund', 'prevalidation', 'voucher');
    }

    /**
     * @param Prevalidation $prevalidation
     * @return array<string, string>
     */
    private function topUpUploadData(Prevalidation $prevalidation): array
    {
        return [
            ...$prevalidation->records()->get()->mapWithKeys(fn (PrevalidationRecord $record) => [
                $record->record_type->key => $record->value,
            ])->all(),
            'test_number' => '3',
        ];
    }

    /**
     * @param Fund $fund
     * @param Voucher $voucher
     * @param array<string, string> $data
     * @throws Throwable
     * @return void
     */
    private function assertTopUpRejected(Fund $fund, Voucher $voucher, array $data): void
    {
        $level = DB::transactionLevel();
        $this->withoutExceptionHandling();

        try {
            $this->makeStorePrevalidationBatchRequest($fund->organization, $fund, [$data], topUp: [
                ['key' => $data['uid'], 'voucher_id' => $voucher->id],
            ]);

            $this->fail('Expected the top-up to be rejected.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Kan geen opwaardeertransactie uitvoeren voor ' . $data['uid'], $exception->getMessage());
            $this->assertSame($level, DB::transactionLevel());
        } finally {
            DB::rollBack($level);
            $this->withExceptionHandling();
        }

        $this->assertSame($level, DB::transactionLevel());
    }
}
