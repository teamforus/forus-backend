<?php

namespace App\Services\PdfToImgService;

use App\Services\PdfToImgService\Contracts\PdfToImgConverterContract;
use App\Services\PdfToImgService\Exceptions\PdfToImgException;
use App\Services\PdfToImgService\Implementations\AwsLambdaPdfToImgConverter;
use App\Services\PdfToImgService\Implementations\LocalPopplerPdfToImgConverter;
use Aws\Credentials\CredentialProvider;
use Aws\Credentials\Credentials;
use Aws\Credentials\CredentialsInterface;
use Aws\Lambda\LambdaClient;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\ServiceProvider;

class PdfToImgServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->singleton(PdfToImgConverterContract::class, function () {
            return $this->makeConverter();
        });

        $this->app->singleton(PdfToImgService::class, function (Application $app) {
            return new PdfToImgService($app->make(PdfToImgConverterContract::class));
        });

        $this->app->singleton('pdf_to_img', function (Application $app) {
            return $app->make(PdfToImgService::class);
        });
    }

    /**
     * @throws PdfToImgException
     * @return PdfToImgConverterContract
     */
    protected function makeConverter(): PdfToImgConverterContract
    {
        $connection = $this->getConnection();
        $driver = strtolower((string) ($connection['driver'] ?? ''));

        if ($this->app->isProduction() && $driver === 'local') {
            throw new PdfToImgException('Local PDF to image converter cannot be used in production.');
        }

        return match ($driver) {
            'aws' => new AwsLambdaPdfToImgConverter(
                client: $this->makeLambdaClient($connection),
                functionName: (string) $connection['lambda']['function_name'],
                qualifier: $this->getQualifier($connection['lambda']),
                disk: (string) $connection['storage']['disk'],
                bucket: $this->getDiskBucket((string) $connection['storage']['disk']),
                inputPrefix: (string) $connection['storage']['input_prefix'],
                outputPrefix: (string) $connection['storage']['output_prefix'],
                cleanup: (bool) $connection['cleanup'],
            ),
            'local' => new LocalPopplerPdfToImgConverter(
                disk: (string) $connection['storage']['disk'],
                path: (string) $connection['storage']['path'],
                pdfinfoBinary: (string) $connection['binaries']['pdfinfo'],
                pdftoppmBinary: (string) $connection['binaries']['pdftoppm'],
                timeout: (int) $connection['timeout'],
            ),
            default => throw new PdfToImgException("Unsupported PDF to image converter driver [$driver]."),
        };
    }

    /**
     * @throws PdfToImgException
     * @return array
     */
    protected function getConnection(): array
    {
        $connectionName = Config::get('forus.pdf_to_img.default');
        $connection = Config::get("forus.pdf_to_img.connections.$connectionName");

        if (!is_array($connection)) {
            throw new PdfToImgException("PDF to image converter connection [$connectionName] is not configured.");
        }

        return $connection;
    }

    /**
     * @param array $connection
     * @throws PdfToImgException
     * @return LambdaClient
     */
    protected function makeLambdaClient(array $connection): LambdaClient
    {
        $lambda = $connection['lambda'] ?? [];

        return new LambdaClient([
            'version' => 'latest',
            'region' => $lambda['region'],
            'credentials' => $this->makeCredentials($connection),
            'http' => [
                'timeout' => (int) $lambda['timeout'],
            ],
        ]);
    }

    /**
     * @param array $connection
     * @throws PdfToImgException
     * @return callable|CredentialsInterface
     */
    protected function makeCredentials(array $connection): callable|CredentialsInterface
    {
        $credentials = $connection['credentials'] ?? [];
        $key = $credentials['key'] ?? null;
        $secret = $credentials['secret'] ?? null;

        if ($key || $secret) {
            if (!$key || !$secret) {
                throw new PdfToImgException(
                    'PDF to image AWS credentials require both key and secret.',
                );
            }

            $token = $credentials['token'] ?? null;

            return new Credentials((string) $key, (string) $secret, $token ? (string) $token : null);
        }

        return CredentialProvider::defaultProvider();
    }

    /**
     * @param string $disk
     * @throws PdfToImgException
     * @return string
     */
    protected function getDiskBucket(string $disk): string
    {
        $bucket = Config::get("filesystems.disks.$disk.bucket");

        if (!is_string($bucket) || $bucket === '') {
            throw new PdfToImgException("PDF to image converter disk [$disk] is missing a bucket.");
        }

        return $bucket;
    }

    /**
     * @param array $connection
     * @return string|null
     */
    protected function getQualifier(array $connection): ?string
    {
        $qualifier = $connection['qualifier'] ?? null;

        return $qualifier ? (string) $qualifier : null;
    }
}
