<?php

namespace App\Console\Commands;

use Mollie\Api\Exceptions\ApiException;
use Illuminate\Support\Facades\App;

class MollieCliCommand extends BaseCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mollie:cli';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    protected string $clientId = '';
    protected string $clientSecret = '';
    protected string $redirectUri = '';
    protected string $accessToken = '';

    /**
     * @return void
     * @throws \Throwable
     */
    public function handle(): void
    {
        if (!App::isProduction()) {
            $this->clientId = config('mollie.client_id', '');
            $this->clientSecret = config('mollie.client_secret', '');
            $this->redirectUri = config('mollie.redirect_url', '');
            $this->accessToken = config('mollie.base_access_token', '');
        }

        $this->askAction();
    }

    /**
     * @return array
     */
    protected function askActionList(): array
    {
        return [
            '## oAuth',
            '[1] Mollie connect (oAuth).',
            '[2] Exchange oAuth code.',
            '[3] Refresh token.',
            '[4] Create client-link.',
            '## Organizations',
            '[5] Read organization.',
            '[6] Read onboarding state.',
            '## Profiles',
            '[7] Create profile.',
            '[8] Read profile.',
            '[9] Read all profiles.',
            '## Payment methods',
            '[10] All payment methods.',
            '[11] Get active payment methods.',
            '[12] Enable payment method.',
            '[13] Disable payment method.',
            '## Payments',
            '[14] Create payment.',
            '[15] Read payment.',
            '[16] Refund payment.',
            '[17] Read payment refund.',
            '## Other',
            '[18] Exit',
        ];
    }

    /**
     * @return void
     * @throws \Throwable
     */
    protected function askAction(): void
    {
        $this->printHeader("Select next action:");
        $this->printList($this->askActionList());
        $action = $this->ask("Please select next step:", 1);

        switch ($action) {
            case 1: $this->mollieConnect(); break;
            case 2: $this->exchangeOauthCode(); break;
            case 3: $this->refreshToken(); break;
            case 4: $this->createClientLink(); break;

            case 5: $this->readOrganization(); break;
            case 6: $this->readOnboardingState(); break;

            case 7: $this->createProfile(); break;
            case 8: $this->readProfile(); break;
            case 9: $this->readAllProfiles(); break;

            case 10: $this->readAllPaymentMethods(); break;
            case 11: $this->readActivePaymentMethods(); break;
            case 12: $this->enablePaymentMethod(); break;
            case 13: $this->disablePaymentMethod(); break;

            case 14: $this->createPayment(); break;
            case 15: $this->readPayment(); break;

            case 16: $this->refundPayment(); break;
            case 17: $this->readPaymentRefund(); break;

            case 18: $this->exit();
            default: $this->printText("Invalid input!\nPlease try again:\n");
        }

        $this->askAction();
    }

    /**
     * @return void
     */
    protected function mollieConnect(): void
    {
        [$clientId, $clientSecret, $redirectUri] = $this->askMollieApp();
        $provider = new \Mollie\OAuth2\Client\Provider\Mollie(compact(
            'clientId', 'clientSecret', 'redirectUri'
        ));

        $authorizationUrl = $provider->getAuthorizationUrl([
            'approval_prompt' => 'force',
            'scope' => [
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_ORGANIZATIONS_READ,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_PROFILES_READ,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_PROFILES_WRITE,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_PAYMENTS_READ,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_PAYMENTS_WRITE,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_ONBOARDING_READ,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_ONBOARDING_WRITE,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_REFUNDS_READ,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_REFUNDS_WRITE,
                \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_BALANCES_READ,
            ],
        ]);

        $this->printText('Auth url:');
        $this->info($authorizationUrl);
        $this->printSeparator();
    }

    protected function exchangeOauthCode(): void {
        [$clientId, $clientSecret, $redirectUri] = $this->askMollieApp();
        $provider = new \Mollie\OAuth2\Client\Provider\Mollie(compact(
            'clientId', 'clientSecret', 'redirectUri'
        ));

        $code = $this->ask('Please provide oAuth code');

        try {
            // Try to get an access token using the authorization code grant.
            $this->printText('Response:');
            $this->info($this->jsonPretty([
                'accessToken' => $provider->getAccessToken('authorization_code', compact('code')),
            ]));
            $this->printSeparator();
        }

        catch (\League\OAuth2\Client\Provider\Exception\IdentityProviderException $e)
        {
            // Failed to get the access token or user details.
            $this->error($e->getMessage());
        }
    }

    /**
     * @return void
     * @throws \League\OAuth2\Client\Provider\Exception\IdentityProviderException
     */
    protected function refreshToken(): void
    {
        [$clientId, $clientSecret, $redirectUri] = $this->askMollieApp();

        $provider = new \Mollie\OAuth2\Client\Provider\Mollie(compact(
            'clientId', 'clientSecret', 'redirectUri'
        ));
        $refreshToken = $this->ask('Please provide refresh token');


        $grant = new \League\OAuth2\Client\Grant\RefreshToken();
        $token = $provider->getAccessToken($grant, ['refresh_token' => $refreshToken]);

        $this->printText('Response:');
        $this->info($this->jsonPretty($token));
        $this->printSeparator();
    }

    /**
     * @return void
     * @throws ApiException
     * @throws \Throwable
     */
    protected function createClientLink(): void
    {
        [$clientId] = $this->askMollieApp();
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setAccessToken($this->ask('Please provide organization accessToken'));

        $orgName = $this->ask('Organization name', substr(fake()->text(20), 0, -1));

        do {
            $clientEmail = $this->ask('Owner email*');
        } while (empty($clientEmail));

        $response = $mollie->clientLinks->create([
            "name" => $orgName,
            "owner" => [
                "email" => $clientEmail,
                "givenName" => $this->ask('Owner given name', fake()->firstName),
                "familyName" => $this->ask('Owner family name', fake()->lastName),
                "locale" => $this->ask('Locale', "nl_NL"),
            ],
            "address" => [
                "streetAndNumber" => $this->ask('Street and number', fake()->streetAddress),
                "postalCode" => $this->ask('Postal code', fake()->postcode),
                "city" => $this->ask('City', "Amsterdam"),
                "country" => $this->ask('Country', "NL"),
            ],
        ]);

        $redirectUri = $response->getRedirectUrl($clientId, token_generator()->generate(64), [
            \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_ORGANIZATIONS_READ,
            \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_PROFILES_READ,
            \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_PROFILES_WRITE,
            \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_PAYMENTS_READ,
            \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_PAYMENTS_WRITE,
            \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_ONBOARDING_READ,
            \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_ONBOARDING_WRITE,
            \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_REFUNDS_READ,
            \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_REFUNDS_WRITE,
            \Mollie\OAuth2\Client\Provider\Mollie::SCOPE_BALANCES_READ,
        ]);

        $this->printText('Response:');
        $this->info($this->jsonPretty($response));
        $this->printSeparator();

        $this->printText('Auth url:');
        $this->info($redirectUri);
        $this->printSeparator();
    }

    /**
     * @return void
     * @throws ApiException
     */
    protected function readOrganization(): void
    {
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setAccessToken($this->askAccessToken());

        $response = $mollie->organizations->current();

        $this->printText('Response:');
        $this->info($this->jsonPretty($response));
        $this->printSeparator();
    }

    /**
     * @return void
     * @throws ApiException
     */
    protected function readOnboardingState(): void
    {
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setAccessToken($this->askAccessToken());

        $response = $mollie->onboarding->get();

        $this->printText('Response:');
        $this->info($this->jsonPretty($response));
        $this->printSeparator();
    }

    /**
     * @return void
     * @throws ApiException
     * @throws \Exception
     */
    protected function createProfile(): void
    {
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setAccessToken($this->askAccessToken());
        $tokenValue = token_generator()->generate(6);

        $response = $mollie->profiles->create([
            'name' => $this->ask('Profile name', "Profile #$tokenValue"),
            'email' => $this->ask('Profile email', "$tokenValue@example.com"),
            'phone' => $this->ask('Profile phone', random_int(111111111, 999999999)),
            'website' => $this->ask('Profile website', "https://$tokenValue.example.com"),
            'mode' => $this->confirm('Test mode', true) ? 'test' : 'live',
        ]);

        $this->printText('Response:');
        $this->info($this->jsonPretty($response));
        $this->printSeparator();
    }

    /**
     * @return void
     * @throws ApiException
     */
    protected function readProfile(): void
    {
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setAccessToken($this->askAccessToken());
        $profileId = $this->ask('Profile id');

        $response = $mollie->profiles->get($profileId);

        $this->printText('Response:');
        $this->info($this->jsonPretty($response));
        $this->printSeparator();
    }

    /**
     * @return void
     * @throws ApiException
     */
    protected function readAllProfiles(): void
    {
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setAccessToken($this->askAccessToken());

        $response = $mollie->profiles->page();

        $this->printText('Response:');
        $this->info($this->jsonPretty($response));
        $this->printSeparator();
    }

    /**
     * @return void
     * @throws ApiException
     */
    protected function readAllPaymentMethods(): void
    {
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setAccessToken($this->askAccessToken());

        $response = $mollie->methods->allAvailable([
            'profileId' => $this->ask('Profile ID'),
            'testmode' => $this->confirm('Test mode', true),
        ]);

        $this->printText('Response:');
        $this->info($this->jsonPretty($response));
        $this->printSeparator();
    }

    /**
     * @return void
     * @throws ApiException
     */
    protected function readActivePaymentMethods(): void
    {
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setAccessToken($this->askAccessToken());

        $response = $mollie->methods->allActive([
            'profileId' => $this->ask('Profile ID'),
            'testmode' => $this->confirm('Test mode', true),
        ]);

        $this->printText('Response:');
        $this->info($this->jsonPretty($response));
        $this->printSeparator();
    }

    /**
     * @return void
     * @throws ApiException
     */
    protected function enablePaymentMethod(): void
    {
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setAccessToken($this->askAccessToken());

        $response = $mollie->profiles
            ->get($this->ask('Profile ID'))
            ->enableMethod($this->ask('Payment method'));

        $this->printText('Response:');
        $this->info($this->jsonPretty($response));
        $this->printSeparator();
    }

    /**
     * @return void
     * @throws ApiException
     */
    protected function disablePaymentMethod(): void
    {
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setAccessToken($this->askAccessToken());

        $response = $mollie->profiles
            ->get($this->ask('Profile ID'))
            ->disableMethod($this->ask('Payment method'));

        $this->printText('Response:');
        $this->info($this->jsonPretty($response));
        $this->printSeparator();
    }

    /**
     * @return void
     * @throws ApiException
     */
    protected function createPayment(): void
    {
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setAccessToken($this->askAccessToken());

        $response = $mollie->payments->create([
            'profileId' => $this->ask('Profile ID'),
            'amount' => [
                "currency" => "EUR",
                "value" => $this->ask('Amount', "2.00"),
            ],
            'description' => $this->ask('description', 'Testing'),
            'redirectUrl' => $this->ask('Redirect url', 'https://example.com/mollie-success'),
            'cancelUrl' => $this->ask('Cancel url', 'https://example.com/mollie-cancel'),
            'locale' => $this->ask('Locale', 'nl_NL'),
            'method' => ['ideal'],
            'metadata' => $this->ask('Metadata', "['foo' => 'bar']"),
            'testmode' => $this->confirm('Test mode', true),
        ]);

        $this->printText('Response:');
        $this->info($this->jsonPretty($response));
        $this->printSeparator();

        $this->printText('Checkout url:');
        $this->info($response->getCheckoutUrl());
        $this->printSeparator();
    }

    /**
     * @return void
     * @throws ApiException
     */
    protected function readPayment(): void
    {
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setAccessToken($this->askAccessToken());

        $response = $mollie->payments->get($this->ask('Payment ID'), [
            'testmode' => $this->confirm('Test mode', true),
        ]);

        $this->printText('Response:');
        $this->info($this->jsonPretty($response));
        $this->printSeparator();
    }

    /**
     * @return void
     * @throws ApiException
     */
    protected function refundPayment(): void
    {
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setAccessToken($this->askAccessToken());

        $paymentId = $this->ask('Payment ID');
        $testMode = $this->confirm('Test mode', true);

        $payment = $mollie->payments->get($paymentId, [
            'testmode' => $testMode,
        ]);

        $response = $payment->refund([
            'amount' => [
                "currency" => "EUR",
                "value" => $this->ask('Amount', $payment->amount->value),
            ],
            'description' => $this->ask('description', 'Testing'),
            'metadata' => $this->ask('Metadata', "['foo' => 'bar']"),
            'testmode' => $testMode,
        ]);

        $this->printText('Response:');
        $this->info($this->jsonPretty($response));
        $this->printSeparator();
    }

    /**
     * @return void
     * @throws ApiException
     */
    protected function readPaymentRefund(): void
    {
        $mollie = new \Mollie\Api\MollieApiClient();
        $mollie->setAccessToken($this->askAccessToken());

        $paymentId = $this->ask('Payment ID');
        $refundId = $this->ask('Refund ID');
        $testMode = $this->confirm('Test mode', true);

        $payment = $mollie->payments->get($paymentId, [
            'testmode' => $testMode,
        ]);

        $response = $payment->getRefund($refundId, [
            'testmode' => $testMode,
        ]);

        $this->printText('Response:');
        $this->info($this->jsonPretty($response));
        $this->printSeparator();
    }

    /**
     * @return array
     */
    protected function askMollieApp(): array
    {
        if ($this->clientId && $this->clientSecret && $this->redirectUri &&
            $this->confirm("Would you like to use '$this->clientId' app?", true)) {
            $clientId = $this->clientId;
            $clientSecret = $this->clientSecret;
            $redirectUri = $this->redirectUri;
        } else {
            $clientId = $this->ask('Please provide clientId');
            $clientSecret = $this->ask('Please provide clientSecret');
            $redirectUri = $this->ask('Please provide redirectUri');
        }

        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->redirectUri = $redirectUri;

        return [$clientId, $clientSecret, $redirectUri];
    }


    /**
     * @return string
     */
    protected function askAccessToken(): string
    {
        if (!$this->accessToken || !$this->confirm("Use '$this->accessToken' access token?", true)) {
            return $this->accessToken = $this->ask('Please provide organization accessToken');
        }

        return $this->accessToken;
    }
}
