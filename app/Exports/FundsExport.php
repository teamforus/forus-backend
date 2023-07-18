<?php

namespace App\Exports;

use App\Exports\Traits\FormatsExportedData;
use App\Models\Fund;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\RegistersEventListeners;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class FundsExport implements FromCollection, WithHeadings, WithColumnFormatting, WithEvents
{
    use Exportable, RegistersEventListeners, FormatsExportedData;

    /**
     * @var array[]
     */
    protected array $formats = [
        NumberFormat::FORMAT_CURRENCY_EUR_SIMPLE => [
            'balance',
            'expenses',
            'transactions',
            'total_top_up',
            'budget_amount_per_voucher',
            'budget_average_per_voucher',
            'budget_total_spent_amount',
            'budget_total_left',
            'budget_vouchers_amount',
            'budget_vouchers_active_amount',
            'budget_vouchers_inactive_amount',
            'budget_vouchers_deactivated_amount',
            'product_vouchers_amount',
            'product_vouchers_active_amount',
            'product_vouchers_inactive_amount',
            'product_vouchers_deactivated_amount',
        ],
        NumberFormat::FORMAT_PERCENTAGE_00 => [
            'budget_total_spent_percentage',
            'budget_total_left_percentage',
            'budget_vouchers_inactive_percentage',
            'budget_vouchers_active_percentage',
        ],
        NumberFormat::FORMAT_TEXT => [
            'budget_vouchers_count',
            'budget_vouchers_inactive_count',
            'budget_vouchers_active_count',
            'budget_vouchers_deactivated_amount',
        ],
    ];

    protected Collection $data;

    protected bool $detailed;

    protected bool $withTotal;

    /**
     * FundsExport constructor.
     * @param EloquentCollection $funds
     * @param bool $detailed
     * @param bool $withTotal
     */
    public function __construct(
        EloquentCollection $funds,
        bool $detailed = true,
        bool $withTotal = true
    ) {
        $this->detailed = $detailed;
        $this->withTotal = $withTotal;
        $this->data = $this->exportTransform($funds);
    }

    /**
     * @return Collection
     */
    public function collection(): Collection
    {
        $data = $this->data->merge($this->getTotals());

        return $data->map(function($item) {
            return array_reduce(array_keys($item), fn($obj, $key) => array_merge($obj, [
                $this->trans($key) => (string) $item[$key],
            ]), []);
        });
    }

    /**
     * @return array|array[]
     */
    protected function getTotals(): array
    {
        return (!$this->detailed && $this->withTotal) ? [[
            "name" => $this->trans("total"),
            "total_top_up" => currency_format($this->data->sum('total_top_up')),
            "balance" => currency_format($this->data->sum('balance')),
            "expenses" => currency_format($this->data->sum('expenses')),
            "transactions" => currency_format($this->data->sum('transactions')),
        ]] : [];
    }

    /**
     * @return array|null
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
        return array_map(fn($key) => $this->trans($key), array_keys($this->first()));
    }

    /**
     * @param Collection $funds
     * @return Collection
     */
    protected function exportTransform(Collection $funds): Collection
    {
        if (!$this->detailed) {
            return $funds->map(fn(Fund $fund) => [
                "name" => $fund->name,
                "total_top_up" => currency_format($fund->budget_total),
                "balance" => currency_format($fund->budget_left),
                "expenses" => currency_format($fund->budget_used),
                "transactions" => currency_format($fund->getTransactionCosts()),
            ]);
        }

        return $funds->map(fn(Fund $fund) => $this->getVoucherData($fund));
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function getVoucherData(Fund $fund): array
    {
        $detailsByType = [
            'budget'  => Fund::getFundDetails($fund->budget_vouchers()->getQuery()),
            'product' => Fund::getFundDetails($fund->product_vouchers()->getQuery()),
        ];

        $voucherData = [
            "name" => $fund->name,
        ];

        foreach ($detailsByType as $type => $details) {
            if ($type == "budget") {
                $budgetUsedPercentage = (float) $details['vouchers_amount'] ? (
                    $fund->budget_used_active_vouchers / $details['vouchers_amount'] * 100) : 0;

                $averagePerVoucher = $details['vouchers_count'] ?
                    $details['vouchers_amount'] / $details['vouchers_count'] : 0;

                $budgetLeftAmount = $details['vouchers_amount'] - $fund->budget_used_active_vouchers;

                $budgetLeftPercentage = (float) $details['vouchers_amount'] ?
                    (($details['vouchers_amount'] - $fund->budget_used_active_vouchers) / $details['vouchers_amount'] * 100) : 0;

                $inactiveVouchersPercentage = (float) $details['vouchers_amount'] ?
                    ($details['inactive_amount'] / $details['vouchers_amount'] * 100) : 0;

                $activeVouchersPercentage = (float) $details['vouchers_amount'] ?
                    ($details['active_amount'] / $details['vouchers_amount'] * 100) : 0;

                $voucherData = array_merge($voucherData, [
                    "budget_amount_per_voucher"            => currency_format($fund->fund_formulas->sum('amount')),
                    "budget_average_per_voucher"           => currency_format($averagePerVoucher),
                    "budget_total_spent_amount"            => currency_format($fund->budget_used_active_vouchers),
                    "budget_total_left_amount"             => currency_format($budgetLeftAmount),
                    "budget_total_spent_percentage"        => currency_format($budgetUsedPercentage / 100, 4),
                    "budget_total_left_percentage"         => currency_format($budgetLeftPercentage / 100, 4),
                    "budget_vouchers_count"                => currency_format($details['vouchers_count'], 0),
                    "budget_vouchers_inactive_count"       => currency_format($details['inactive_count'], 0),
                    "budget_vouchers_inactive_percentage"  => currency_format($inactiveVouchersPercentage / 100, 4),
                    "budget_vouchers_active_percentage"    => currency_format($activeVouchersPercentage / 100, 4),
                    "budget_vouchers_active_count"         => currency_format($details['active_count'], 0),
                    "budget_vouchers_deactivated_count"    => currency_format($details['deactivated_count'], 0),
                ]);
            }

            $voucherData = array_merge($voucherData, [
                "{$type}_vouchers_amount"               => currency_format($details['vouchers_amount']),
                "{$type}_vouchers_active_amount"        => currency_format($details['active_amount']),
                "{$type}_vouchers_inactive_amount"      => currency_format($details['inactive_amount']),
                "{$type}_vouchers_deactivated_amount"   => currency_format($details['deactivated_amount']),
            ]);
        }

        return $voucherData;
    }

    /**
     * @param string $key
     * @return string|null
     */
    protected function trans(string $key): ?string
    {
        return trans("export.funds.$key");
    }
}