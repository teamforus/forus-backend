<?php


namespace App\Services\AwsSecretService;


use Aws\Credentials\CredentialProvider;
use Aws\Exception\AwsException;
use Aws\SecretsManager\SecretsManagerClient;
use Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;

class AwsSecretServiceProvider extends ServiceProvider
{
    /**
     * @var SecretsManagerClient $client
     */
    private $client;

    /**
     * @var array $secrets
     */
    private $secrets;

    /**
     * Bootstrap AWS secret manager service
     *
     * @throws AwsException
     */
    public function boot(): void
    {
        if ($this->app->environment() === 'production') {
            $credentials = CredentialProvider::defaultProvider();

            $this->client = new SecretsManagerClient([
                'version' => 'latest',
                'region' => 'eu-west-1',
                'credentials' => $credentials
            ]);


            try {
                $this->secrets = $this->client->listSecrets();

                $this->checkSecrets();
            }
            catch (AwsException $e) {}
        }
    }

    /**
     * Checks for matching aws secret names
     */
    private function checkSecrets(): void
    {
        foreach (config('forus.aws.secret_names') as $configKey => $secretName) {
            foreach ($this->secrets['SecretList'] as $secret) {
                if ($secret['Name'] === $secretName) {
                    $this->setPasswords($configKey, $secret['Name']);
                }
            }
        }
    }

    /**
     * Sets the passwords in the config file
     *
     * @param string $config
     * @param string $secretId
     */
    private function setPasswords(string $config, string $secretId): void
    {
        $secretValue = $this->client->getSecretValue([
            'SecretId' => $secretId
        ]);

        if(isset($secretValue['SecretString'])) {
            $secret = json_decode($secretValue['SecretString'], true);
        }
        else {
            $secret = json_decode(base64_decode($secretValue['SecretBinary'], true));
        }

        $config = str_replace('_', '.', $config);

        if (config($config) !== $secret['password']) {
            config([$config => $secret['password']]);

            Artisan::call('config:clear');
        }
    }
}
