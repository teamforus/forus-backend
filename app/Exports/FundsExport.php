<?php

namespace App\Exports;

use App\Models\Fund;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Class FundsExport
 * @package App\Exports
 */
class FundsExport implements FromCollection, WithHeadings, WithEvents
{
    use Exportable, RegistersEventListeners;

    protected $data;
    protected $detailed;
    protected $headers;

    /**
     * VoucherTransactionsSponsorExport constructor.
     * @param EloquentCollection $funds
     * @param bool $detailed
     */
    public function __construct(EloquentCollection $funds, $detailed = true)
    {
        $this->detailed = $detailed;
        $this->data = $this->exportTransform($funds);
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        return $this->data->merge(!$this->detailed ? [[
            $this->trans("name") => $this->trans("total"),
            $this->trans("total_top_up") => currency_format($this->data->sum($this->trans("total_top_up"))),
            $this->trans("balance") => currency_format($this->data->sum($this->trans("balance"))),
            $this->trans("expenses") => currency_format($this->data->sum($this->trans("expenses"))),
            $this->trans("transactions") => currency_format($this->data->sum($this->trans("transactions"))),
        ]] : []);
    }

    /**
     * @return array|Collection|null
     */
    public function first(): ?array
    {
        return $this->collection()->first();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return array_keys($this->first());
    }

    /**
     * @param Collection $funds
     * @return Collection
     */
    protected function exportTransform(Collection $funds): Collection
    {
        if (!$this->detailed) {
            return $funds->map(function(Fund $fund) {
                return [
                    $this->trans("name") => $fund->name,
                    $this->trans("total_top_up") => currency_format($fund->budget_total),
                    $this->trans("balance") => currency_format($fund->budget_left),
                    $this->trans("expenses") => currency_format($fund->budget_used),
                    $this->trans("transactions") => currency_format($fund->getTransactionCosts()),
                ];
            });
        }

        return $funds->map(function(Fund $fund) {
            $details = Fund::getFundDetails($fund->budget_vouchers()->getQuery());

            $budgetUsedPercentage = $details['vouchers_amount'] ? (
                $fund->budget_used_active_vouchers / $details['vouchers_amount'] * 100) : 0;

            $averagePerVoucher = $details['vouchers_count'] ?
                $details['vouchers_amount'] / $details['vouchers_count'] : 0;

            $budgetLeft = $details['vouchers_amount'] - $fund->budget_used_active_vouchers;

            $budgetLeftPercentage = $details['vouchers_amount'] ?
                (($details['vouchers_amount'] - $fund->budget_used_active_vouchers) / $details['vouchers_amount'] * 100) : 0;

            $inactiveVouchersPercentage = $details['vouchers_amount'] ?
                ($details['inactive_amount'] / $details['vouchers_amount'] * 100) : 0;

            $activeVouchersPercentage = $details['vouchers_amount'] ?
                ($details['active_amount'] / $details['vouchers_amount'] * 100) : 0;

            // vouchers_amount
            //used_active_vouchers

            return collect([
                "name"                          => $fund->name,
                "amount_per_voucher"            => currency_format($fund->fund_formulas->sum('amount')),
                "average_per_voucher"           => currency_format($averagePerVoucher),
                "total_spent_amount"            => currency_format($fund->budget_used_active_vouchers),
                "total_spent_percentage"        => currency_format($budgetUsedPercentage) . ' %',
                "total_left"                    => currency_format($budgetLeft),
                "total_left_percentage"         => currency_format($budgetLeftPercentage) . ' %',

                "vouchers_amount"               => currency_format($details['vouchers_amount']),
                "vouchers_count"                => (string) $details['vouchers_count'],
                "vouchers_inactive_count"       => currency_format($details['inactive_count'], 0),
                "vouchers_inactive_amount"      => currency_format($details['inactive_amount']),
                "vouchers_inactive_percentage"  => currency_format($inactiveVouchersPercentage) . ' %',
                "vouchers_active_amount"        => (string) $details['active_amount'],
                "vouchers_active_percentage"    => currency_format($activeVouchersPercentage) . ' %',
                "vouchers_active_count"         => currency_format($details['active_count'], 0),
            ])->mapWithKeys(function($value, $key) {
                return [$this->trans($key) => $value];
            })->toArray();
        });
    }

    /**
     * @param string $key
     * @return string
     */
    protected function trans(string $key): ?string
    {
        return trans("export.funds.$key");
    }
}