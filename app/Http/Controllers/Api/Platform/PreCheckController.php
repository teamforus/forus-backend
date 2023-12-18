<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\PreChecks\CalculatePreCheckRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\ImplementationPreChecksResource;
use App\Models\Fund;
use App\Models\Implementation;
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
        $records = array_pluck($request->input('records', []), 'value', 'key');
        $fundsQuery = Implementation::queryFundsByState('active');

        if ($identity) {
            $fundsQuery->whereDoesntHave('vouchers', fn (
                Builder|Voucher $builder
            ) => VoucherQuery::whereActive($builder->where([
                'identity_address' => $identity->address,
            ])));
        }

        $availableFunds = (new FundSearch($request->only([
            'q', 'tag', 'tag_id', 'organization_id',
        ]), $fundsQuery))->query()->get();

        $funds = PreCheck::calculateTotalsPerFund($availableFunds, $records);
        $fundsValid = array_where($funds, fn ($fund) => $fund['is_valid']);

        $amountTotal = array_sum(array_pluck($funds, 'amount_total'));
        $amountTotalValid = array_sum(array_pluck($fundsValid, 'amount_total'));

        return new JsonResponse([
            'funds' => $funds,
            'funds_valid' => $fundsValid,
            'amount_total' => $amountTotal,
            'amount_total_locale' => currency_format_locale($amountTotal),
            'amount_total_valid' => $amountTotalValid,
            'amount_total_valid_locale' => currency_format_locale($amountTotalValid),
        ]);
    }
}
