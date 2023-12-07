<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\PreChecks\CalculatePreCheckRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\ImplementationPreChecksResource;
use App\Models\PreCheck;
use App\Models\Voucher;
use App\Scopes\Builders\VoucherQuery;
use App\Searches\FundSearch;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;

class PreCheckController extends Controller
{
    /**
     * @param BaseFormRequest $request
     * @return ImplementationPreChecksResource
     */
    public function index(BaseFormRequest $request): ImplementationPreChecksResource
    {
        return ImplementationPreChecksResource::create($request->implementation());
    }

    /**
     * Calculate pre-check totals based on record inputs
     *
     * @param CalculatePreCheckRequest $request
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function calculateTotals(CalculatePreCheckRequest $request): JsonResponse
    {
        $identity = $request->identity();
        $implementation = $request->implementation();
        $records = array_pluck($request->input('records', []), 'value', 'key');

        $availableFundsQuery = $implementation->funds()->whereDoesntHave('vouchers', fn (
            Builder|Voucher $builder
        ) => VoucherQuery::whereActive($builder->where([
            'identity_address' => $identity->address,
        ])));

        $availableFunds = (new FundSearch($request->only([
            'q', 'tag', 'tag_id', 'organization_id',
        ]), $availableFundsQuery))->query()->get();

        $totalsPerFund = PreCheck::calculateTotalsPerFund($availableFunds, $records);
        $amountTotal = array_sum(array_pluck($totalsPerFund, 'amount_total'));

        return new JsonResponse([
            'funds' => $totalsPerFund,
            'amount_total' => $amountTotal,
            'amount_total_currency' => currency_format($amountTotal),
        ]);
    }
}
