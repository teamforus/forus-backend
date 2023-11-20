<?php

namespace App\Http\Controllers;

use App\Http\Requests\BaseFormRequest;
use App\Http\Requests\Mollie\WebhookMollieRequest;
use App\Models\Implementation;
use App\Models\ReservationExtraPayment;
use App\Services\MollieService\Exceptions\MollieApiException;
use App\Services\MollieService\MollieService;
use Illuminate\Http\RedirectResponse;

class MollieController extends Controller
{
    /**
     * @param BaseFormRequest $request
     * @return RedirectResponse
     */
    public function processCallback(BaseFormRequest $request): RedirectResponse
    {
        $code = $request->get('code');
        $state = $request->get('state');

        if (!$code || !$state) {
            return redirect(Implementation::general()->urlProviderDashboard());
        }

        try {
            $connection = MollieService::make()->exchangeOauthCode($code, $state);

            return redirect(Implementation::general()->urlProviderDashboard(
                $connection ? "/organizations/$connection->organization_id/payment-methods" : '/',
            ));
        } catch (MollieApiException $e) {
            MollieService::logError('Failed to redirect the user after mollie sign-up', $e);
            return redirect(Implementation::general()->urlProviderDashboard());
        }
    }

    /**
     * @param WebhookMollieRequest $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    public function processWebhook(WebhookMollieRequest $request)
    {
        ReservationExtraPayment::query()
            ->where('payment_id', $request->get('id'))
            ->where('type', ReservationExtraPayment::TYPE_MOLLIE)
            ->first()
            ?->fetchAndUpdateMolliePayment();

        return response('');
    }
}
