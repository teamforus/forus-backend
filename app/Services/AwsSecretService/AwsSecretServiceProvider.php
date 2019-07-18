<?php


namespace App\Services\AwsSecretService;


use Aws\Exception\AwsException;
use Aws\SecretsManager\SecretsManagerClient;
use Config;
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
     * @var array $configs
     */
    private $configs = [
        'database.connections.mysql.password'
    ];

    /**
     * Bootstrap AWS secret manager service
     *
     * @throws AwsException
     */
    public function boot(): void
    {
        if ($this->app->environment() === 'production') {
            $this->client = new SecretsManagerClient([
                'profile' => 'default',
                'version' => 'latest',
                'region' => 'eu-west-1'
            ]);

            try {
                $this->secrets = $this->client->listSecrets([]);

                $this->checkSecrets();
            }
            catch (AwsException $e) {}
        }
    }

    /**
     * Checks for matching aws secret names
     */
    private function checkSecrets()
    {
        foreach ($this->configs as $config) {
            if ($secretName = config('forus.aws.secret_names.' . $config)) {
                foreach ($this->secrets['SecretList'] as $secret) {
                    if ($secret['Name'] === $secretName) {
                        $this->setPasswords($config, $secret['Name']);
                    }
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

        if(isset($result['SecretString'])) {
            $secret = json_decode($secretValue['SecretString']);
        }
        else {
            $secret = json_decode(base64_decode($secretValue['SecretBinary']));
        }

        config([$config => $secret['password']]);
    }
}
