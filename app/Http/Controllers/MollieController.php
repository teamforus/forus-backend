<?php

namespace App\Http\Controllers;

use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\Mollie\WebhookMollieRequest;
use App\Models\Implementation;
use App\Models\ReservationExtraPayment;
use App\Services\MollieService\Data\ForusTokenData;
use App\Services\MollieService\Exceptions\MollieException;
use App\Services\MollieService\MollieService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class MollieController extends Controller
{
    /**
     * @param BaseFormRequest $request
     * @return RedirectResponse
     */
    public function processCallback(BaseFormRequest $request): RedirectResponse
    {
        if (!$request->get('code') || !$request->get('state')) {
            return redirect(Implementation::general()->urlProviderDashboard());
        }

        try {
            $connection = MollieService::make(new ForusTokenData())->exchangeOauthCode(
                $request->get('code'),
                $request->get('state'),
            );

            return redirect(Implementation::general()->urlProviderDashboard(
                $connection ? "/organizations/$connection->organization_id/payment-methods" : '/',
            ));
        } catch (MollieException $e) {
            MollieService::logError('Failed to redirect the user after mollie sign-up', $e);
            return redirect(Implementation::general()->urlProviderDashboard());
        }
    }

    /**
     * @param WebhookMollieRequest $request
     * @return JsonResponse
     */
    public function processWebhook(WebhookMollieRequest $request): JsonResponse
    {
        $extraPayment = ReservationExtraPayment::query()
            ->where('payment_id', $request->get('id'))
            ->where('type', ReservationExtraPayment::TYPE_MOLLIE)
            ->first();

        try {
            $extraPayment?->fetchAndUpdateMolliePayment(null);
        } catch (MollieException $e) {
            MollieService::logError('Failed to fetch webhook mollie update', $e);
        }

        return new JsonResponse();
    }
}
