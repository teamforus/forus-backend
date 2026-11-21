<?php

namespace Tests\Traits;

use Illuminate\Support\Facades\Http;

trait FakesMicrosoftGraph
{
    /**
     * @param array<string, string> $tokensByTenant
     * @return void
     */
    protected function fakeMicrosoftGraph(array $tokensByTenant): void
    {
        $responses = [];

        foreach ($tokensByTenant as $tenantId => $accessToken) {
            $responses["https://login.microsoftonline.com/$tenantId/oauth2/v2.0/token"] = Http::response([
                'access_token' => $accessToken,
                'expires_in' => 3600,
            ]);
        }

        $responses['https://graph.microsoft.com/v1.0/users/*/sendMail'] = Http::response([], 202);

        Http::fake($responses);
    }
}
