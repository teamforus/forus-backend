<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\PreChecks\CalculatePreCheckRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\ImplementationPreChecksResource;
use App\Models\PreCheck;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

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
        $records = array_pluck($request->input('records', []), 'value', 'key');
        $availableFunds = PreCheck::getAvailableFunds($request);

        $funds = PreCheck::calculateTotalsPerFund($availableFunds, $records);
        $fundsValid = array_where($funds, fn ($fund) => $fund['is_valid']);

        $amountTotal = array_sum(array_pluck($funds, 'amount_total'));
        $amountTotalValid = array_sum(array_pluck($fundsValid, 'amount_total'));
        $products_count_total = array_sum(array_pluck($funds, 'product_count'));
        $products_amount_total = array_sum(array_pluck($funds, 'products_amount_total'));

        return new JsonResponse([
            'funds' => $funds,
            'funds_valid' => $fundsValid,
            'amount_total' => $amountTotal,
            'amount_total_locale' => currency_format_locale($amountTotal),
            'amount_total_valid' => $amountTotalValid,
            'products_count_total' => $products_count_total,
            'products_amount_total' => currency_format_locale($products_amount_total),
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
        $records = array_pluck($request->input('records', []), 'value', 'key');
        $availableFunds = PreCheck::getAvailableFunds($request);

        $funds = PreCheck::calculateTotalsPerFund($availableFunds, $records);

        $domPdfFile = resolve('dompdf.wrapper')->loadView('pdf.pre_check_totals', [
            'funds' => $funds,
            'date_locale' => format_date_locale(now()),
            'implementation_key' => $request->implementation()->key,
        ]);

        return $domPdfFile->download('pre-check-totals.pdf');
    }
}
