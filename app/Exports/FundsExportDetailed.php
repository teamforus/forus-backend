<?php

namespace App\Exports;

use App\Exports\Base\BaseFieldedExport;
use App\Models\Fund;
use App\Statistics\Funds\FinancialOverviewStatistic;
use App\Statistics\Funds\FinancialOverviewStatisticQueries;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class FundsExportDetailed extends BaseFieldedExport
{
    protected static string $transKey = 'funds';

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'name',
        'budget_amount_per_voucher',
        'budget_average_per_voucher',
        'budget_total_spent_amount',
        'budget_total_left_amount',
        'budget_total_spent_percentage',
        'budget_total_left_percentage',
        'budget_vouchers_count',
        'budget_vouchers_inactive_count',
        'budget_vouchers_inactive_percentage',
        'budget_vouchers_active_percentage',
        'budget_vouchers_active_count',
        'budget_vouchers_deactivated_count',
        'budget_children_count',

        'budget_vouchers_amount',
        'budget_vouchers_active_amount',
        'budget_vouchers_inactive_amount',
        'budget_vouchers_deactivated_amount',

        'product_vouchers_amount',
        'product_vouchers_active_amount',
        'product_vouchers_inactive_amount',
        'product_vouchers_deactivated_amount',

        'payout_vouchers_amount',
    ];

    /**
     * @var array|string[]
     */
    protected static array $payoutVoucherOnlyKeys = ['payout_vouchers_amount'];

    /**
     * @var array[]
     */
    protected array $formats = [
        NumberFormat::FORMAT_CURRENCY_EUR => [
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
            'payout_vouchers_amount',
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

    /**
     * FundsExport constructor.
     * @param EloquentCollection $funds
     * @param Carbon $from
     * @param Carbon $to
     * @param array $fields
     */
    public function __construct(
        EloquentCollection $funds,
        protected Carbon $from,
        protected Carbon $to,
        protected array $fields
    ) {
        $this->data = $this->export($funds);
    }

    /**
     * @param bool $withPayoutFields
     * @return array
     */
    public static function getExportFields(bool $withPayoutFields = true): array
    {
        $fields = $withPayoutFields
            ? static::$exportFields
            : array_filter(static::$exportFields, static function (string $item) {
                return !in_array($item, static::$payoutVoucherOnlyKeys);
            });

        return array_reduce($fields, fn ($list, $key) => array_merge($list, [[
            'key' => $key,
            'name' => static::trans($key),
        ]]), []);
    }

    /**
     * @param bool $withPayoutFields
     * @return array
     */
    public static function getExportFieldsRaw(bool $withPayoutFields = true): array
    {
        return $withPayoutFields
            ? static::$exportFields
            : array_filter(static::$exportFields, static function (string $item) {
                return !in_array($item, static::$payoutVoucherOnlyKeys);
            });
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function export(Collection $data): Collection
    {
        return $this->exportTransform($data);
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        $data = $data->map(fn (Fund $fund) => array_only($this->getRow($fund), $this->fields));

        if (!$data->first(fn (array $item) => ($item['budget_children_count'] ?? 0) > 0)) {
            $data = $data->map(function (array $item) {
                unset($item['budget_children_count']);

                return $item;
            });
        }

        return $this->transformKeys($data);
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function getRow(Fund $fund): array
    {
        $detailsByType = [
            'budget' => FinancialOverviewStatistic::getFundDetails($fund->budget_vouchers()->getQuery(), $this->from, $this->to),
            'product' => FinancialOverviewStatistic::getFundDetails($fund->product_vouchers()->getQuery(), $this->from, $this->to),
            'payout' => FinancialOverviewStatistic::getFundPayoutDetails($fund->payout_vouchers()->getQuery(), $this->from, $this->to),
        ];

        $usedActiveVouchers = FinancialOverviewStatisticQueries::getBudgetFundUsedActiveVouchers($fund, $this->from, $this->to);

        $voucherData = [
            'name' => $fund->name,
        ];

        foreach ($detailsByType as $type => $details) {
            if ($type == 'budget') {
                $budgetUsedPercentage = (float) $details['vouchers_amount'] ? (
                    $fund->budget_used_active_vouchers / $details['vouchers_amount'] * 100
                ) : 0;

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
                    'budget_amount_per_voucher' => currency_format($fund->fund_formulas->sum('amount')),
                    'budget_average_per_voucher' => currency_format($averagePerVoucher),
                    'budget_total_spent_amount' => currency_format($usedActiveVouchers),
                    'budget_total_left_amount' => currency_format($budgetLeftAmount),
                    'budget_total_spent_percentage' => currency_format($budgetUsedPercentage / 100, 4),
                    'budget_total_left_percentage' => currency_format($budgetLeftPercentage / 100, 4),
                    'budget_vouchers_count' => currency_format($details['vouchers_count'], 0),
                    'budget_vouchers_inactive_count' => currency_format($details['inactive_count'], 0),
                    'budget_vouchers_inactive_percentage' => currency_format($inactiveVouchersPercentage / 100, 4),
                    'budget_vouchers_active_percentage' => currency_format($activeVouchersPercentage / 100, 4),
                    'budget_vouchers_active_count' => currency_format($details['active_count'], 0),
                    'budget_vouchers_deactivated_count' => currency_format($details['deactivated_count'], 0),
                    'budget_children_count' => $details['children_count'],
                ]);
            }

            $voucherData = array_merge($voucherData, [
                "{$type}_vouchers_amount" => currency_format(Arr::get($details, 'vouchers_amount', 0)),
                "{$type}_vouchers_active_amount" => currency_format(Arr::get($details, 'active_amount', 0)),
                "{$type}_vouchers_inactive_amount" => currency_format(Arr::get($details, 'inactive_amount', 0)),
                "{$type}_vouchers_deactivated_amount" => currency_format(Arr::get($details, 'deactivated_amount', 0)),
            ]);
        }

        return $voucherData;
    }
}
