<?php

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Voucher;

/**
 * @noinspection PhpUnused
 */
class MakeCreationLogForOlderVouchers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $vouchers = Voucher::whereDoesntHave('logs', function(Builder $builder) {
            $builder->whereIn('event', Voucher::EVENTS_CREATED);
        })->with([
            'employee' => function($builder) {
                /** @var Builder|SoftDeletes $builder */
                $builder->withTrashed();
            },
            'product' => function($builder) {
                /** @var Builder|SoftDeletes $builder */
                $builder->withTrashed();
            },
        ])->get();

        $this->migrateVouchers($vouchers);
    }

    /**
     * @param Collection|Voucher[] $vouchers
     */
    protected function migrateVouchers(Collection $vouchers)
    {
        foreach ($vouchers as $voucher) {
            $voucher->log(Voucher::EVENT_CREATED_BUDGET, $this->getVoucherLogModels($voucher), [
                'note' => $voucher->note,
                'voucher_amount' => currency_format($voucher->amount),
                'voucher_amount_locale' => currency_format_locale($voucher->amount),
            ])->forceFill([
                'original' => false,
                'updated_at' => $voucher->created_at,
                'created_at' => $voucher->created_at,
                'identity_address' => $voucher->employee->identity_address ?? $voucher->identity_address,
            ])->save();
        }
    }

    /**
     * @param Voucher $voucher
     * @return array
     */
    protected function getVoucherLogModels(Voucher $voucher): array {
        return array_merge([
            'fund' => $voucher->fund,
            'voucher' => $voucher,
            'sponsor' => $voucher->fund->organization,
            'employee' => $voucher->employee,
        ], $voucher->isProductType() ? [
            'product' => $voucher->product,
            'provider' => $voucher->product->organization,
        ] : []);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void {}
}
