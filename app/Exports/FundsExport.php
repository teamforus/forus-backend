<?php

namespace App\Exports;

use App\Exports\Base\BaseExport;
use App\Models\Fund;
use App\Statistics\Funds\FinancialOverviewStatisticQueries;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class FundsExport extends BaseExport
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
     * @param Builder|Relation|Fund $builder
     * @param array $fields
     * @param Carbon $from
     * @param Carbon $to
     */
    public function __construct(
        Builder|Relation|Fund $builder,
        protected array $fields,
        protected Carbon $from,
        protected Carbon $to,
    ) {
        parent::__construct($builder, $fields);
    }

    /**
     * @param Model|Fund $model
     * @return array
     */
    protected function getRow(Model|Fund $model): array
    {
        $total = FinancialOverviewStatisticQueries::getFundBudgetTotal($model, $this->from, $this->to);
        $used = FinancialOverviewStatisticQueries::getFundBudgetUsed($model, $this->from, $this->to);
        $costs = FinancialOverviewStatisticQueries::getFundTransactionCosts($model, $this->from, $this->to);

        $this->accumulateTotals($total, $used, $costs);

        return [
            'name' => $model->name,
            'balance' => currency_format(round($total - $used, 2)),
            'expenses' => currency_format($used),
            'transactions' => currency_format($costs),
            'total_top_up' => currency_format($total),
        ];
    }

    /**
     * @param float $total
     * @param float $used
     * @param float $costs
     * @return void
     */
    protected function accumulateTotals(float $total, float $used, float $costs): void
    {
        $this->totals['balance'] += ($total - $used);
        $this->totals['expenses'] += $used;
        $this->totals['transactions'] += $costs;
        $this->totals['total_top_up'] += $total;
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

        return array_reduce(array_keys($row), fn ($obj, $key) => array_merge($obj, [
            $fieldLabels[$key] => $row[$key],
        ]), []);
    }
}
