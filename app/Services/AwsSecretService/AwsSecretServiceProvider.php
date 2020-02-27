<?php

namespace App\Services\AwsSecretService;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use Aws\Credentials\CredentialProvider;
use Aws\Exception\AwsException;
use Aws\SecretsManager\SecretsManagerClient;
use Config;

class AwsSecretServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap AWS secret manager service
     */
    public function boot(): void
    {
        if (!config('forus.aws_secrets_manager.enabled')) {
            return;
        }

        try {
            $this->updateConfigsBySecretClient(new SecretsManagerClient([
                'version'       => config('forus.aws_secrets_manager.version'),
                'region'        => config('forus.aws_secrets_manager.region'),
                'credentials'   => CredentialProvider::defaultProvider()
            ]));
        } catch (AwsException $e) {
            logger()->error(sprintf(
                'Error while retrieving aws secrets: %s',
                $e->getMessage()
            ));
        }
    }

    /**
     * Sets the passwords in the config file
     *
     * @param SecretsManagerClient $client
     */
    private function updateConfigsBySecretClient(SecretsManagerClient $client): void
    {
        $collections = array_filter(config('forus.aws_secrets_manager.collections'));

        foreach ($collections as $collectionId => $collection) {
            $secretCollection = $client->getSecretValue([
                'SecretId' => $collectionId
            ]);

            if (isset($secretCollection['SecretString'])) {
                $secretCollection = $secretCollection['SecretString'];
            } else {
                $secretCollection = base64_decode($secretCollection['SecretBinary'], true);
            }

            $secretCollection = json_decode($secretCollection, true);

            foreach ($collection as $secretKey => $configKey) {
                Config::set($configKey, $secretCollection[$secretKey]);
            }
        }

        Artisan::call('config:clear');
    }
}
