<?php

namespace App\Http\Controllers;

use App\Http\Requests\BNG\RedirectBNGBankConnectionRequest;
use App\Http\Requests\BNG\RedirectBNGVoucherTransactionBulkRequest;
use App\Models\BankConnection;
use App\Models\VoucherTransactionBulk;
use App\Services\BNGService\BNGService;
use App\Services\BNGService\Data\AuthData;
use Illuminate\Http\RedirectResponse;
use Throwable;

class BNGController extends Controller
{
    /**
     * @param RedirectBNGBankConnectionRequest $request
     * @param BankConnection $connection
     * @return RedirectResponse
     */
    public function bankConnectionRedirect(
        RedirectBNGBankConnectionRequest $request,
        BankConnection $connection
    ): RedirectResponse {
        $code = $request->get('code');
        /** @var BNGService $bngService */
        $bngService = resolve('bng_service');

        if (!$code) {
            return redirect($connection->dashboardDetailsUrl(null, 'canceled'));
        }

        try {
            $response = $bngService->exchangeAuthCode($code, new AuthData('', $connection->auth_params));

            $connection->update([
                'code' => $code,
                'access_token' => $response->getAccessToken(),
                'expire_at' => now()->addSeconds($response->getExpiresIn()),
            ]);

            $connection->setMonetaryAccounts($connection->fetchConnectionMonetaryAccounts());
            $connection->setActive();
            $connection->updateFundBalances();
        } catch (Throwable $e) {
            $connection->logBngError('Connection redirect', $e);
            return redirect($connection->dashboardDetailsUrl(null, 'unknown'));
        }

        return redirect($connection->dashboardDetailsUrl(true));
    }

    /**
     * @param RedirectBNGVoucherTransactionBulkRequest $request
     * @param VoucherTransactionBulk $bulk
     * @return RedirectResponse
     */
    public function voucherTransactionBulkRedirect(
        RedirectBNGVoucherTransactionBulkRequest $request,
        VoucherTransactionBulk $bulk
    ): RedirectResponse {
        $code = $request->get('code');
        /** @var BNGService $bngService */
        $bngService = resolve('bng_service');

        if (!$code) {
            return redirect($bulk->dashboardDetailsUrl(null, 'canceled'));
        }

        try {
            $response = $bngService->exchangeAuthCode($code, new AuthData('', $bulk->auth_params));
            $access_token = $response->getAccessToken();

            $bulk->update(compact('code', 'access_token'));
            $bulk->updatePaymentStatus();
        } catch (Throwable $e) {
            $error_message = $e->getMessage();
            $error_trace = $e->getTraceAsString();

            $request->logger()->error(json_encode(compact('error_message', 'error_trace'), 128));
            $bulk->logError(compact('error_message'));

            return redirect($bulk->dashboardDetailsUrl(null, 'unknown'));
        }

        return redirect($bulk->dashboardDetailsUrl(true));
    }
}
