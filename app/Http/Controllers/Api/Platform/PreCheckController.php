<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\PreChecks\CalculatePreCheckRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\ImplementationPreChecksResource;
use App\Models\PreCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;

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
     * Calculate pre-check totals based on record inputs.
     *
     * @param CalculatePreCheckRequest $request
     * @return JsonResponse
     * @noinspection PhpUnused
     */
    public function calculateTotals(CalculatePreCheckRequest $request): JsonResponse
    {
        $records = Arr::pluck($request->input('records', []), 'value', 'key');
        $availableFunds = PreCheck::getAvailableFunds($request->identity(), $request->only([
            'q', 'tag_id', 'organization_id',
        ]));

        $funds = PreCheck::calculateTotalsPerFund($availableFunds, $records);
        $fundsValid = Arr::where($funds, fn ($fund) => $fund['is_valid']);

        $amountTotal = array_sum(Arr::pluck($funds, 'amount_total'));
        $amountTotalValid = array_sum(Arr::pluck($fundsValid, 'amount_total'));
        $productsCountTotal = array_sum(Arr::pluck($funds, 'product_count'));
        $productsAmountTotal = array_sum(Arr::pluck($funds, 'products_amount_total'));

        return new JsonResponse([
            'funds' => $funds,
            'funds_valid' => $fundsValid,
            'amount_total' => $amountTotal,
            'amount_total_locale' => currency_format_locale($amountTotal),
            'amount_total_valid' => $amountTotalValid,
            'products_count_total' => $productsCountTotal,
            'products_amount_total' => currency_format_locale($productsAmountTotal),
            'amount_total_valid_locale' => currency_format_locale($amountTotalValid),
        ]);
    }

    /**
     * @param CalculatePreCheckRequest $request
     * @return \Illuminate\Http\Response
     * @noinspection PhpUnused
     */
    public function downloadPDF(CalculatePreCheckRequest $request): Response
    {
        $records = Arr::pluck($request->input('records', []), 'value', 'key');
        $availableFunds = PreCheck::getAvailableFunds($request->identity(), $request->only([
            'q', 'tag_id', 'organization_id',
        ]));

        $funds = PreCheck::calculateTotalsPerFund($availableFunds, $records);

        $domPdfFile = resolve('dompdf.wrapper')->loadView('pdf.pre_check_totals', [
            'funds' => $funds,
            'date_locale' => format_date_locale(now()),
            'implementation_key' => $request->implementation()->key,
        ]);

        return $domPdfFile->download('pre-check-totals.pdf');
    }
}
