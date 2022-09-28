<?php


namespace App\Services\IConnectApiService;

use App\Models\Fund;
use App\Services\BNGService\TmpFile;
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
    protected Fund $fund;

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

    private string $api_url;
    private string $iconnect_api_oin;
    private string $iconnect_target_binding;

    private string $cert_trust_path;

    /**
     * @param Fund $fund
     */
    public function __construct(Fund $fund) {
        $this->fund = $fund;

        $configs = static::getConfigs($fund);
        $isSandbox = Arr::get($configs, 'env') === self::ENV_SANDBOX;

        if (!in_array(Arr::get($configs, 'env'), self::ENVIRONMENTS, true)) {
            throw new RuntimeException('Invalid iConnection "env" type.');
        }

        $this->cert_trust_path = Arr::get($configs, 'cert_trust_path', '');
        $this->iconnect_api_oin = $this->fund->fund_config->iconnect_api_oin;
        $this->iconnect_target_binding = $this->fund->fund_config->iconnect_target_binding;
        $this->api_url = $isSandbox ? self::URL_SANDBOX : Str::finish($this->fund->fund_config->iconnect_base_url, '/');
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
     * @return ResponseInterface|null
     */
    private function request(string $url, array $data = []): ?ResponseInterface
    {
        $guzzleClient = new Client();
        $options = $this->makeRequestOptions($data);
        $options['verify'] = $this->cert_trust_path;
        $options['http_errors'] = false;

        try {
            return $guzzleClient->request(self::METHOD_GET, $url, $options);
        } catch (\Throwable $e) {
            logger()->error($e->getMessage());
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
            'apikey' => '',
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
            'cert' => "",
            'ssl_key' => "",
            'headers' => $this->makeRequestHeaders(),
            'connect_timeout' => 10,
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
     * @param Fund $fund
     * @return array|null
     */
    public static function getConfigs(Fund $fund): ?array
    {
        $keyTmpFile = new TmpFile($fund->fund_config->iconnect_key);
        $certTmpFile = new TmpFile($fund->fund_config->iconnect_certificate);
        $certTrustTmpFile = new TmpFile($fund->fund_config->iconnect_cert_trust);

        $config = [
            'env'       => $fund->fund_config->iconnect_env,
            'key_path'  => $keyTmpFile->path(),
            'cert_path' => $certTmpFile->path(),
            'cert_trust_path' => $certTrustTmpFile->path(),
        ];

        $keyTmpFile->close();
        $certTmpFile->close();
        $certTrustTmpFile->close();

        return $config;
    }
}
