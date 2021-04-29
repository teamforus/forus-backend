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
            $this->trans("name") => 'Total',
            $this->trans("total") => currency_format($this->data->sum($this->trans("total"))),
            $this->trans("current") => currency_format($this->data->sum($this->trans("current"))),
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
                    $this->trans("total") => currency_format($fund->budget_total),
                    $this->trans("current") => currency_format($fund->budget_left),
                    $this->trans("expenses") => currency_format($fund->budget_used),
                    $this->trans("transactions") => currency_format($fund->getTransactionCosts()),
                ];
            });
        }

        return $funds->map(function(Fund $fund) {
            $details = $fund->getFundDetails();

            return [
                $this->trans("name") => $fund->name,
                $this->trans("amount_per_voucher")       => $fund->getMaxAmountPerVoucher(),
                $this->trans("average_per_voucher")      => $fund->getMaxAmountSumVouchers(),
                $this->trans("total_vouchers_amount")    => currency_format($details['total_vouchers_amount']),
                $this->trans("total_vouchers_count")     => (string) ($details['active_count'] + $details['inactive_count']),
                $this->trans("vouchers_inactive_amount") => currency_format($details['inactive_amount']),
                $this->trans("vouchers_inactive_percentage") => $details['inactive_percentage'].' %',
                $this->trans("vouchers_inactive_count")  => (string) $details['inactive_count'],
                $this->trans("vouchers_active_amount")   => currency_format($details['active_amount']),
                $this->trans("total_spent_amount")       => currency_format($fund->budget_used),
                $this->trans("total_spent_percentage")   => currency_format(
                    $fund->budget_total ? ($fund->budget_used / $fund->budget_total * 100) : 0) . ' %',
                $this->trans("total_left")               => currency_format($fund->budget_left),
            ];
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