<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * App\Models\FundTopUpTransaction
 *
 * @property int $id
 * @property int $fund_top_up_id
 * @property float|null $amount
 * @property string|null $bank_transaction_id
 * @property int|null $bank_connection_account_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\FundTopUp $fund_top_up
 * @property-read \App\Models\BankConnectionAccount $bank_connection_account
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction query()
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction whereBankConnectionAccountId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction whereBankTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction whereFundTopUpId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|FundTopUpTransaction whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class FundTopUpTransaction extends Model
{
    protected $fillable = [
        'fund_top_up_id', 'bank_transaction_id', 'amount'
    ];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     * @noinspection PhpUnused
     */
    function fund_top_up(): BelongsTo
    {
        return $this->belongsTo(FundTopUp::class);
    }

    function bank_connection_account(): BelongsTo
    {
        return $this->belongsTo(BankConnectionAccount::class);
    }

    /**
     * @param Request $request
     * @param Fund|null $fund
     * @return Builder
     */
    public static function search(Request $request, Fund $fund = null): Builder
    {
        /** @var Builder $query */
        $query = self::query();

        if ($fund) {
            $query->whereHas('fund_top_up', static function (Builder $query) use ($fund) {
                $query->where('fund_id', $fund->id);
            });
        }

        if ($request->has('q') && $q = $request->input('q', '')) {
            $query->where(static function (Builder $query) use ($q) {
                $query->whereHas('fund_top_up', static function (Builder $query) use ($q) {
                    $query->where('code', 'LIKE', "%$q%");
                });

                $query->orWhereHas('bank_connection_account', static function (Builder $query) use ($q) {
                    $query->where('monetary_account_iban', 'LIKE', "%$q%");
                });
            });
        }

        if ($amount_min = $request->input('amount_min')) {
            $query->where('amount', '>=', $amount_min);
        }

        if ($amount_max = $request->input('amount_max')) {
            $query->where('amount', '<=', $amount_max);
        }

        if ($request->has('code') && $code = $request->input('code')) {
            $query->whereHas('fund_top_up', static function (Builder $query) use ($code) {
                $query->where('code', 'LIKE', "%$code%");
            });
        }

        if ($request->has('iban') && $iban = $request->input('iban')) {
            $query->whereHas('bank_connection_account', static function (Builder $query) use ($iban) {
                $query->where('monetary_account_iban', 'LIKE', "%$iban%");
            });
        }

        if ($request->has('from') && $from = $request->input('from')) {
            $query->where(
                'created_at','>=',
                (Carbon::createFromFormat('Y-m-d', $from))->startOfDay()->format('Y-m-d H:i:s')
            );
        }

        if ($request->has('to') && $to = $request->input('to')) {
            $query->where(
                'created_at','<=',
                (Carbon::createFromFormat('Y-m-d', $to))->endOfDay()->format('Y-m-d H:i:s')
            );
        }

        return $query;
    }
}
