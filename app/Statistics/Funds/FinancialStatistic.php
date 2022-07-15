<?php


namespace App\Statistics\Funds;

use App\Models\BaseModel;
use App\Models\Organization;
use App\Models\VoucherTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class FinancialStatistic
 * @package App\Statistics
 */
class FinancialStatistic
{
    /**
     * @param Organization $sponsor
     * @param array $options
     * @return array
     */
    public function getFilters(Organization $sponsor, array $options = []): array
    {
        $queries = new FinancialStatisticQueries();
        $dates = $this->makeDates($options);
        $options = array_merge($options, [
            'date_from' => array_first($dates)['from'] ?? null,
            'date_to' => array_last($dates)['to'] ?? null
        ]);

        return [
            'filters' => [
                'product_categories' => $queries->getFilterProductCategories($sponsor, $options),
                'providers' => $queries->getFilterProviders($sponsor, $options),
                'postcodes' => $queries->getFilterPostcodes($sponsor, $options),
                'funds' => $queries->getFilterFunds($sponsor, $options),
            ],
        ];
    }

    /**
     * @param Organization $sponsor
     * @param array $options
     * @return array
     */
    public function getStatistics(Organization $sponsor, array $options): array
    {
        $dates = $this->makeDates($options);
        $datesData = $this->makeDatesData($options, $sponsor, $dates);

        $lowest_transaction = $datesData->pluck('lowest_transaction')->sortBy('amount')->first();
        $highest_transaction = $datesData->pluck('highest_transaction')->sortByDesc('amount')->first();
        $highest_daily_transaction = $datesData->pluck('highest_daily_transaction')->sortByDesc('amount')->first();

        return [
            'dates' => $datesData->toArray(),
            'totals' => [
                'amount' => $datesData->sum('amount'),
                'count' => $datesData->sum('count'),
            ],
            "lowest_transaction" => $lowest_transaction,
            "highest_transaction" => $highest_transaction,
            "highest_daily_transaction" => $highest_daily_transaction,
        ];
    }

    /**
     * @param array $options
     * @return array
     */
    protected function makeDates(array $options): array {
        $type = array_get($options, 'type');
        $date = Carbon::createFromDate(array_get($options, 'type_value'))->startOfDay();

        $dates = [];

        if ($type === 'year') {
            $startDate = $date->clone()->startOfYear();

            do {
                $dates[] = [
                    'from' => $startDate->copy(),
                    'to' => $startDate->copy()->endOfMonth(),
                ];
            } while ($startDate->addMonth()->year == $date->year);
        } elseif ($type === 'quarter') {
            $startDate = $date->clone()->startOfQuarter();

            do {
                $dates[] = [
                    'from' => $startDate->copy()->startOfWeek(),
                    'to' => $startDate->copy()->endOfWeek(),
                ];
            } while ($startDate->addWeek()->quarter == $date->quarter);
        } elseif ($type === 'month') {
            $startDate = $date->clone()->startOfMonth();

            do {
                $dates[] = [
                    'from' => $startDate->copy()->startOfDay(),
                    'to' => $startDate->copy()->endOfDay(),
                ];
            } while ($startDate->addDay()->month == $date->month);
        }

        return $dates;
    }

    /**
     * @param array $options
     * @param Organization $sponsor
     * @param array $dates
     * @return Collection
     */
    public function makeDatesData(array $options, Organization $sponsor, array $dates): Collection
    {
        $type = array_get($options, 'type');

        foreach ($dates as $index => $dateItem) {
            /** @var Carbon $dateFrom */
            $dateFrom = $dateItem['from'];

            /** @var Carbon $dateTo */
            $dateTo = $dateItem['to'];

            $transactionsQuery = $this->getTransactionsQuery($options, $sponsor, $dateFrom, $dateTo);
            $dates[$index] = $this->getDateData($type, $transactionsQuery, $dateFrom);
        }

        return collect($dates);
    }

    public function getTransactionsQuery(
        array $options,
        Organization $sponsor,
        Carbon $dateFrom,
        Carbon $dateTo
    ): Builder {
        $queries = new FinancialStatisticQueries();

        // global filter sponsor organization
        return $queries->getFilterTransactionsQuery($sponsor, array_merge($options, [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]));
    }

    /**
     * @param VoucherTransaction|null $transaction
     * @return array|null
     */
    protected function getTransactionData(?VoucherTransaction $transaction) : ?array
    {
        return $transaction ? array_merge($transaction->only('id', 'amount'), [
            'provider' => $transaction->provider->name,
        ]) : null;
    }

    /**
     * @param string $type
     * @param Builder $transactionsQuery
     * @param Carbon $dateFrom
     * @return array
     */
    protected function getDateData(
        string $type,
        Builder $transactionsQuery,
        Carbon $dateFrom
    ): array  {
        /** @var VoucherTransaction|BaseModel|null $highest_transaction */
        $highest_transaction = (clone $transactionsQuery)->orderByDesc('amount')->first();
        $transactionOverview = (clone $transactionsQuery)->selectRaw(
            'count(*) as `count`, sum(`amount`) as amount, min(`amount`) as lowest_transaction_amount'
        )->first();

        /** @var VoucherTransaction|BaseModel|null $highest_daily_transaction */
        $highest_daily_transaction = (clone $transactionsQuery)->groupBy(
            DB::raw('Date(`created_at`)')
        )->orderByDesc('amount')->selectRaw(
            'sum(`voucher_transactions`.`amount`) as `amount`, Date(`created_at`) as `date`'
        )->first();

        return array_merge($transactionOverview->toArray(), [
            "key" => $dateFrom->copy()->startOfDay()->formatLocalized($this->dateFormatStr($type)),
            "highest_transaction" => $this->getTransactionData($highest_transaction),
            "highest_daily_transaction" => $highest_daily_transaction ? array_merge($highest_daily_transaction->toArray(), [
                'date_locale' => format_date_locale($highest_daily_transaction->date ?? null),
            ]) : $highest_daily_transaction,
        ]);
    }

    /**
     * @param string $type
     * @return string
     */
    protected function dateFormatStr(string $type): string
    {
        return [
            'year' => '%B, %Y',
            'month' => '%d %b',
            'quarter' => 'Week %U',
        ][$type];
    }
}