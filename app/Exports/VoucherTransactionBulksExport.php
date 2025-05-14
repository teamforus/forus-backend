<?php

namespace App\Exports;

use App\Exports\Base\BaseFieldedExport;
use App\Models\Organization;
use App\Models\VoucherTransactionBulk;
use App\Scopes\Builders\VoucherTransactionBulkQuery;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class VoucherTransactionBulksExport extends BaseFieldedExport
{
    /**
     * @var array|string[]
     */
    public static array $exportFields = [
        'id',
        'quantity',
        'amount',
        'bank_name',
        'date_transaction',
        'state',
    ];
    protected static string $transKey = 'voucher_transaction_bulks';

    /**
     * @param Request $request
     * @param Organization $organization
     * @param array $fields
     */
    public function __construct(Request $request, Organization $organization, protected array $fields)
    {
        $this->data = $this->export($request, $organization);
    }

    /**
     * @param Request $request
     * @param Organization $organization
     * @return Collection
     */
    protected function export(Request $request, Organization $organization): Collection
    {
        $query = VoucherTransactionBulkQuery::order(
            VoucherTransactionBulk::search($request, $organization),
            $request->get('order_by'),
            $request->get('order_dir')
        );

        $data = $query->with([
            'voucher_transactions',
            'bank_connection.bank',
        ])->withCount([
            'voucher_transactions',
        ])->get();

        return $this->exportTransform($data);
    }

    /**
     * @param Collection $data
     * @return Collection
     */
    protected function exportTransform(Collection $data): Collection
    {
        return $this->transformKeys($data->map(fn (VoucherTransactionBulk $transactionBulk) => array_only(
            $this->getRow($transactionBulk),
            $this->fields,
        )));
    }

    /**
     * @param VoucherTransactionBulk $transactionBulk
     * @return array
     */
    protected function getRow(VoucherTransactionBulk $transactionBulk): array
    {
        return [
            'id' => $transactionBulk->id,
            'quantity' => $transactionBulk->voucher_transactions_count,
            'amount' => currency_format($transactionBulk->voucher_transactions->sum('amount')),
            'bank_name' => $transactionBulk->bank_connection->bank->name,
            'date_transaction' => format_datetime_locale($transactionBulk->created_at),
            'state' => trans("export.voucher_transaction_bulks.state-values.$transactionBulk->state"),
        ];
    }
}
