<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\PreChecks\CalculatePreCheckRequest;
use App\Http\Requests\BaseFormRequest;
use App\Http\Resources\ImplementationPreChecksResource;
use App\Models\PreCheck;
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
        $records = array_pluck($request->input('records', []), 'value', 'key');
        $availableFunds = PreCheck::getAvailableFunds($request);

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

    /**
     * @param CalculatePreCheckRequest $request
     * @return \Illuminate\Http\Response
     * @noinspection PhpUnused
     */
    public function downloadPDF(CalculatePreCheckRequest $request): \Illuminate\Http\Response
    {
        $records = array_pluck($request->input('records', []), 'value', 'key');
        $availableFunds = PreCheck::getAvailableFunds($request);;

        $domPdf = resolve('dompdf.wrapper')->setOption(
            'chroot',
            realpath(base_path()).'/public/assets/pre-check-totals-export'
        );

        $funds = PreCheck::calculateTotalsPerFund($availableFunds, $records);

        $domPdfFile = $domPdf->loadView('pdf.pre_check_totals', [
            'date_locale' => format_date_locale(now()),
            'implementation_key' => $request->implementation()->key,
            'funds' => $funds,
        ]);

        return $domPdfFile->download('pre-check-totals.pdf');
    }
}
