<?php


namespace App\Services\IConnectApiService;

use App\Services\IConnectApiService\Objects\Person;
use App\Services\IConnectApiService\Responses\ResponseData;
use GuzzleHttp\Client;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

/**
 * Class IConnect
 * @package App\Services\IConnectApiService
 */
class IConnect
{
    private const METHOD_GET = 'GET';

    private const ENV_PRODUCTION = 'production';
    private const ENV_SANDBOX = 'sandbox';

    private const ENVIRONMENTS = [
        self::ENV_SANDBOX,
        self::ENV_PRODUCTION,
    ];

    private const URL_SANDBOX = 'https://apitest.locgov.nl/iconnect/brpmks/1.3.0/';

    private array $with = [
        'parents' => 'ouders',
        'children' => 'kinderen',
        'partners' => 'partners',
    ];

    private string $iconnect_api_oin;
    private string $iconnect_target_binding;
    private string $api_url;

    private string $cert_trust_path;
    private array $configs;

    /**
     * @param string $iconnectApiOin
     * @param string $targetBinding
     * @param string $apiUrl
     */
    public function __construct(string $iconnectApiOin, string $targetBinding, string $apiUrl) {
        $configs = static::getConfigs();
        $isSandbox = Arr::get($configs, 'env') === self::ENV_SANDBOX;

        if (!in_array(Arr::get($configs, 'env'), self::ENVIRONMENTS, true)) {
            throw new RuntimeException('Invalid iConnection "env" type.');
        }

        $this->configs = $configs;
        $this->cert_trust_path = Arr::get($configs, 'cert_trust_path', '');
        $this->iconnect_api_oin = $iconnectApiOin;
        $this->iconnect_target_binding = $targetBinding;
        $this->api_url = $isSandbox ? self::URL_SANDBOX : Str::finish($apiUrl, '/');
    }

    /**
     * @param string $bsn
     * @param array $with can contain parents,children,partners
     * @param array $fields can contain burgerservicenummer,naam.voorletters
     * @return Person|null
     * @throws \Throwable
     */
    public function getPerson(string $bsn, array $with = [], array $fields = []): ?Person
    {
        $url = $this->api_url . "ingeschrevenpersonen/$bsn";
        $query = $this->buildQuery($with, $fields);
        $response = $this->request($url, $query);

        return $response ? new Person(new ResponseData($this->request($url, $query))) : null;
    }

    /**
     * Make the request to the API
     *
     * @param string $url
     * @param array $data
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function request(string $url, array $data = []): ?ResponseInterface
    {
        $guzzleClient = new Client();
        $options = $this->makeRequestOptions($data);
        $options['verify'] = $this->cert_trust_path;
        $options['http_errors'] = false;

        try {
            return $guzzleClient->request(self::METHOD_GET, $url, $options);
        } catch (\Throwable $exception) {
            logger()->error($exception->getMessage());
            return null;
        }
    }

    /**
     * Make request headers
     *
     * @return string[]
     */
    private function makeRequestHeaders(): array
    {
        return [
            'apikey' => Arr::get($this->configs, 'header_key', ''),
            'x-doelbinding' => $this->iconnect_target_binding,
            'x-origin-oin' => $this->iconnect_api_oin,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Make Guzzle request options
     *
     * @param array $data
     * @return array
     */
    private function makeRequestOptions(array $data): array {
        return [
            'cert' => [Arr::get($this->configs, 'cert_path'), Arr::get($this->configs, 'cert_pass')],
            'ssl_key' => [Arr::get($this->configs, 'key_path'), Arr::get($this->configs, 'key_pass')],
            'headers' => $this->makeRequestHeaders(),
            'connect_timeout' => Arr::get($this->configs, 'connect_timeout', 10),
            'query' => $data,
        ];
    }

    /**
     * @param array $with
     * @param array $fields
     * @return array
     */
    private function buildQuery(array $with = [], array $fields = []): array
    {
        $with = array_only($this->with, $with);

        sort($with);
        sort($fields);

        return array_filter([
            'expand' => implode(',', count($with) ? $with : []),
            'fields' => implode(',', count($fields) ? $fields : []),
        ]);
    }

    /**
     * @return array|null
     */
    public static function getConfigs(): ?array
    {
        $storage = resolve('filesystem')->disk('local');
        $configPath = config('iconnect.config_path');

        if ($storage->has($configPath)) {
            try {
                $config = json_decode($storage->get($configPath), true);

                return array_merge([
                    'key_path' => $storage->path($config['key_storage_path'] ?? ''),
                    'cert_path' => $storage->path($config['cert_storage_path'] ?? ''),
                    'cert_trust_path' => $storage->path($config['cert_storage_trust_path'] ?? ''),
                ], $config);
            } catch (FileNotFoundException $e) {
                logger()->error($e->getMessage());
            }
        }

        return null;
    }
}
