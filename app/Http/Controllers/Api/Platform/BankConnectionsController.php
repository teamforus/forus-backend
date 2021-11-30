<?php

namespace App\Http\Controllers\Api\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\BankConnections\RedirectBankConnectionRequest;
use App\Models\BankConnection;
use bunq\Exception\BunqException;
use GuzzleHttp\Exception\BadResponseException;

class BankConnectionsController extends Controller
{
    /**
     * Redirect user to the sponsor dashboard after bank authorization approval
     *
     * @param RedirectBankConnectionRequest $request
     * @throws BunqException
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function redirect(RedirectBankConnectionRequest $request)
    {
        $bankConnection = BankConnection::whereRedirectToken($request->input('state'))->firstOrFail();
        $dashboardUrl = "/organizations/$bankConnection->organization_id/bank-connections";

        if (!$bankConnection->isPending() || $request->has('error')) {
            $error = $bankConnection->isPending() ? $request->input('error') : 'not_pending';

            if ($error == 'access_denied') {
                $bankConnection->setRejected();
            }

            return $this->redirectWithQuery($bankConnection, $dashboardUrl, compact('error'));
        }

        try {
            $code = $request->input('code');
            $access_token = $bankConnection->getTokenByCode($code);

            $bankConnection->update(compact('code', 'access_token'));
            $bankConnection->updateContext($bankConnection->makeNewContext());
            $bankConnection->updateMonetaryAccount($bankConnection->getMonetaryAccounts()[0] ?? []);
            $bankConnection->setActive();
        } catch (BadResponseException $exception) {
            $errorBody = @json_decode($exception->getResponse()->getBody()->getContents(), true);

            return $this->redirectWithQuery($bankConnection, $dashboardUrl, [
                'error' => $errorBody['error'] ?? 'Unknown error.',
            ]);
        }

        return $this->redirectWithQuery($bankConnection, $dashboardUrl, [
            'success' => true,
        ]);
    }

    /**
     * @param BankConnection $bankConnection
     * @param string $dashboardUrl
     * @param array $params
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    protected function redirectWithQuery(
        BankConnection $bankConnection,
        string $dashboardUrl,
        array $params = []
    ) {
        return redirect($bankConnection->implementation->urlSponsorDashboard($dashboardUrl, array_merge([
            'connection_id' => $bankConnection->id,
        ], $params)));
    }
}
