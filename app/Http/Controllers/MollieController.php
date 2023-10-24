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

        if (!$code) {
            return redirect(Implementation::general()->urlProviderDashboard());
        }

        $mollieService = new MollieService();
        try {
            $connection = $mollieService->exchangeOauthCode($code, $request->get('state'));

            $url = Implementation::general()->urlProviderDashboard($connection ? sprintf(
                "/organizations/%s/payment-methods",
                $connection->organization_id
            ) : '/');

            return redirect($url);
        } catch (MollieApiException $e) {
            return redirect(Implementation::general()->urlProviderDashboard());
        }
    }
}
