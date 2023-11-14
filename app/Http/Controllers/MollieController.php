<?php

namespace App\Http\Controllers;

use App\Http\Requests\BaseFormRequest;
use App\Models\Implementation;
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
}
