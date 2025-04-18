<?php

namespace App\Exports;

use App\Exports\Base\BaseFieldedExport;
use App\Models\Fund;
use App\Statistics\Funds\FinancialOverviewStatisticQueries;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class FundsExport extends BaseFieldedExport
{
    protected static string $transKey = 'funds';

    /**
     * @var array|int[]
     */
    protected array $totals = [
        'balance' => 0,
        'expenses' => 0,
        'transactions' => 0,
        'total_top_up' => 0,
    ];

    /**
     * @var array|string[]
     */
    protected static array $exportFields = [
        'name',
        'balance',
        'expenses',
        'transactions',
        'total_top_up',
    ];

    /**
     * @var array[]
     */
    protected array $formats = [
        NumberFormat::FORMAT_CURRENCY_EUR => [
            'balance',
            'expenses',
            'transactions',
            'total_top_up',
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
        $this->data = collect($this->data)->merge($this->getTotals());
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
        return $this->transformKeys(
            $data->map(fn (Fund $fund) => array_only($this->getRow($fund), $this->fields))
        );
    }

    /**
     * @param Fund $fund
     * @return array
     */
    protected function getRow(Fund $fund): array
    {
        $total = FinancialOverviewStatisticQueries::getFundBudgetTotal($fund, $this->from, $this->to);
        $used = FinancialOverviewStatisticQueries::getFundBudgetUsed($fund, $this->from, $this->to);
        $costs = FinancialOverviewStatisticQueries::getFundTransactionCosts($fund, $this->from, $this->to);

        $this->totals = [
            'balance' => $this->totals['balance'] + ($total - $used),
            'expenses' => $this->totals['expenses'] + $used,
            'transactions' => $this->totals['transactions'] + $costs,
            'total_top_up' => $this->totals['total_top_up'] + $total,
        ];

        return [
            'name' => $fund->name,
            'balance' => currency_format(round($total - $used, 2)),
            'expenses' => currency_format($used),
            'transactions' => currency_format($costs),
            'total_top_up' => currency_format($total),
        ];
    }

    /**
     * @return array|array[]
     */
    protected function getTotals(): array
    {
        $fieldLabels = array_pluck(static::getExportFields(), 'name', 'key');

        $row = array_only([
            'name' => static::trans('total'),
            'balance' => currency_format($this->totals['balance']),
            'expenses' => currency_format($this->totals['expenses']),
            'transactions' => currency_format($this->totals['transactions']),
            'total_top_up' => currency_format($this->totals['total_top_up']),
        ], $this->fields);

        $row = array_reduce(array_keys($row), fn ($obj, $key) => array_merge($obj, [
            $fieldLabels[$key] => $row[$key],
        ]), []);

        return [$row];
    }
}
