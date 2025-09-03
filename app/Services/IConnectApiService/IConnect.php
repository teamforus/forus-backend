<?php

namespace App\Services\IConnectApiService;

use App\Models\Organization;
use App\Services\IConnectApiService\Objects\Person;
use App\Services\IConnectApiService\Responses\ResponseData;
use GuzzleHttp\Client;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

class IConnect
{
    private const string METHOD_GET = 'GET';

    private const string ENV_PRODUCTION = 'production';
    private const string ENV_SANDBOX = 'sandbox';

    private const array ENVIRONMENTS = [
        self::ENV_SANDBOX,
        self::ENV_PRODUCTION,
    ];

    private const string URL_SANDBOX = 'https://lab.api.mijniconnect.nl/iconnect/apihcbrp/mks/v1/';

    private array $with = [
        'parents' => 'ouders',
        'children' => 'kinderen',
        'partners' => 'partners',
    ];

    private string $api_url;
    private array $configs;

    /**
     * @param Organization $organization
     */
    public function __construct(Organization $organization)
    {
        $configs = $this->organizationToConfigs($organization);

        if (!in_array(Arr::get($configs, 'env'), self::ENVIRONMENTS, true)) {
            throw new RuntimeException('Invalid iConnection "env" type.');
        }

        $this->configs = $configs;
        $this->api_url = Arr::get($configs, 'env') === self::ENV_SANDBOX ?
            self::URL_SANDBOX :
            Str::finish(Arr::get($configs, 'base_url'), '/');
    }

    /**
     * @param string $bsn
     * @param array $with can contain parents,children,partners
     * @param array $fields can contain burgerservicenummer,naam.voorletters
     * @throws Throwable
     * @return Person|null
     */
    public function getPerson(string $bsn, array $with = [], array $fields = []): ?Person
    {
        $url = $this->api_url . "ingeschrevenpersonen/$bsn";
        $query = $this->buildQuery($with, $fields);
        $response = $this->request($url, $query);

        return $response ? new Person(new ResponseData($this->request($url, $query))) : null;
    }

    /**
     * Make the request to the API.
     *
     * @param string $url
     * @param array $data
     * @return ResponseInterface|null
     */
    private function request(string $url, array $data = []): ?ResponseInterface
    {
        $guzzleClient = new Client();
        $options = $this->makeRequestOptions($data);

        $keyTmpFile = new TmpFile(Arr::get($this->configs, 'key', ''));
        $certTmpFile = new TmpFile(Arr::get($this->configs, 'cert', ''));
        $certTrustTmpFile = new TmpFile(Arr::get($this->configs, 'cert_trust', ''));

        $options['cert'] = [$certTmpFile->path(), Arr::get($this->configs, 'cert_pass')];
        $options['ssl_key'] = [$keyTmpFile->path(), Arr::get($this->configs, 'key_pass')];
        $options['verify'] = $certTrustTmpFile->path();
        $options['http_errors'] = false;

        try {
            return $guzzleClient->request(self::METHOD_GET, $url, $options);
        } catch (Throwable $e) {
            Log::channel('iconnect')->error($e->getMessage());

            return null;
        } finally {
            $keyTmpFile->close();
            $certTmpFile->close();
            $certTrustTmpFile->close();
        }
    }

    /**
     * Make request headers.
     *
     * @return string[]
     */
    private function makeRequestHeaders(): array
    {
        return [
            'apikey' => Arr::get($this->configs, 'header_key', ''),
            'x-doelbinding' => Arr::get($this->configs, 'target_binding', ''),
            'x-origin-oin' => Arr::get($this->configs, 'api_oin', ''),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Make Guzzle request options.
     *
     * @param array $data
     * @return array
     */
    private function makeRequestOptions(array $data): array
    {
        return [
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
     * @param Organization $organization
     * @return array
     */
    private function organizationToConfigs(Organization $organization): array
    {
        return [
            'env' => $organization->iconnect_env,
            'api_oin' => $organization->iconnect_api_oin,
            'cert' => $organization->iconnect_cert,
            'cert_pass' => $organization->iconnect_cert_pass,
            'cert_trust' => $organization->iconnect_cert_trust,
            'key' => $organization->iconnect_key,
            'key_pass' => $organization->iconnect_key_pass,
            'base_url' => $organization->iconnect_base_url,
            'target_binding' => $organization->iconnect_target_binding,
        ];
    }
}
