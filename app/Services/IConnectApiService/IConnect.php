<?php


namespace App\Services\IConnectApiService;

use App\Services\IConnectApiService\Responses\Child;
use App\Services\IConnectApiService\Responses\ParentPerson;
use App\Services\IConnectApiService\Responses\Partner;
use App\Services\IConnectApiService\Responses\Person;
use GuzzleHttp\Client;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Class IConnect
 * @package App\Services\IConnectApiService
 */
class IConnect
{
    private const METHOD_GET = 'GET';
    private const CACHE_KEY = 'iconnect_';

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

    private int $cache_time;
    private string $cert_trust_path;
    private array $configs;

    /**
     * @param string $iconnectApiOin
     * @param string $targetBinding
     * @param string $apiUrl
     */
    public function __construct(string $iconnectApiOin, string $targetBinding, string $apiUrl) {
        $configs = static::getConfigs();

        if (!in_array(Arr::get($configs, 'env'), self::ENVIRONMENTS, true)) {
            throw new RuntimeException('Invalid iConnection "env" type.');
        }

        $this->configs = $configs;
        $this->cache_time = Arr::get($configs, 'cache_time', 60) * 60;
        $this->cert_trust_path = Arr::get($configs, 'cert_trust_path', '');
        $this->iconnect_api_oin = $iconnectApiOin;
        $this->iconnect_target_binding = $targetBinding;

        $this->api_url = Arr::get($configs, 'env') === self::ENV_SANDBOX
            ? self::URL_SANDBOX
            : Str::finish($apiUrl, '/');
    }

    /**
     * @param string $bsn
     * @return Child[]
     */
    public function getChildren(string $bsn): array
    {
        $url = $this->getEndpoint('children', $bsn);
        $result = $this->request(self::METHOD_GET, $url);

        return array_map(function(array $item) {
            return new Child($item);
        }, $result['success'] ? $result['response_body']['_embedded'] ?? [] : []);
    }

    /**
     * @param string $bsn
     * @param int $id
     * @return Child|null
     */
    public function getChild(string $bsn, int $id): ?Child
    {
        $url = $this->getEndpoint('child', $bsn, $id);
        $result = $this->request(self::METHOD_GET, $url);

        return $result['success'] ? new Child($result['response_body'] ?? []) : null;
    }

    /**
     * @param string $bsn
     * @return ParentPerson[]
     */
    public function getParents(string $bsn): array
    {
        $url = $this->getEndpoint('parents', $bsn);
        $result = $this->request(self::METHOD_GET, $url);

        return array_map(function(array $item) {
            return new ParentPerson($item);
        }, $result['success'] ? $result['response_body']['_embedded'] ?? [] : []);
    }

    /**
     * @param string $bsn
     * @param int $id
     * @return ParentPerson|null
     */
    public function getParent(string $bsn, int $id): ?ParentPerson
    {
        $url = $this->getEndpoint('parent', $bsn, $id);
        $result = $this->request(self::METHOD_GET, $url);

        return $result['success'] ? new ParentPerson($result['response_body'] ?? []) : null;
    }

    /**
     * @param string $bsn
     * @return Partner[]
     */
    public function getPartners(string $bsn): array
    {
        $url = $this->getEndpoint('partners', $bsn);
        $result = $this->request(self::METHOD_GET, $url);

        return $result['success'] ? [new Partner($result['response_body']['_embedded'] ?? [])] : [];
    }

    /**
     * @param string $bsn
     * @param int $id
     * @return Partner|null
     */
    public function getPartner(string $bsn, int $id): ?Partner
    {
        $url = $this->getEndpoint('partners', $bsn, $id);
        $result = $this->request(self::METHOD_GET, $url);

        return $result['success'] ? new Partner($result['response_body'] ?? []) : null;
    }

    /**
     * @param string $bsn
     * @param array $with can contain parents,children,partners
     * @param array $fields can contain burgerservicenummer,naam.voorletters
     * @return Person|null
     */
    public function getPerson(string $bsn, array $with = [], array $fields = []): ?Person
    {
        $url = $this->getEndpoint('person', $bsn);
        $query = $this->buildQuery($with, $fields);

        $result = $this->remember("person-$bsn", $query, function() use ($url, $query) {
            $request = $this->request(self::METHOD_GET, $url, $query);
            return $request['success'] ? $request['response_body'] : null;
        });

        return $result ? new Person($result) : null;
    }

    /**
     * @param array $with can contain parents,children,partners
     * @param array $fields can contain burgerservicenummer,naam.voorletters
     * @param array $search
     * @return Person[]
     */
    public function search(array $with = [], array $fields = [], array $search = []): array
    {
        $url = $this->getEndpoint('search');
        $query = array_merge($this->buildQuery($with, $fields), $this->getSearchParams($search));
        $result = $this->request(self::METHOD_GET, $url, $query);

        return array_map(function(array $item) {
            return new Partner($item);
        }, $result['success'] ? $result['response_body']['_embedded']['ingeschrevenpersonen'] ?? [] : []);
    }

    /**
     * @param array $search
     * @return array
     */
    private function getSearchParams(array $search = []): array
    {
        $arr = [
            'citizen_number' => 'burgerservicenummer',
            'gender' => 'geslachtsaanduiding',

            'name' => 'naam__geslachtsnaam',
            'first_name' => 'naam__voornamen',
            'last_name' => 'naam__geslachtsnaam',
            'name_prefix' => 'naam__voorvoegsel',

            'birth_date' => 'geboorte__datum',
            'birth_country' => 'geboorte__land',
            'birth_place' => 'geboorte__plaats',

            'including_deceased_persons' => 'inclusiefOverledenPersonen',

            'postcode' => 'verblijfplaats__postcode',
            'house_number' => 'verblijfplaats__huisnummer',
            'street' => 'verblijfplaats__straat',

            'municipality_of_registration' => 'verblijfplaats__gemeenteVanInschrijving',
            'house_letter' => 'verblijfplaats__huisletter',
            'house_number_addition' => 'verblijfplaats__huisnummertoevoeging',
            'residence_identification_code_number_designation' => 'verblijfplaats__identificatiecodenummeraanduiding',
            'name_public_space' => 'verblijfplaats__naamopenbareruimte',
        ];

        return array_reduce($search, function(array $query, string $key) use ($arr, $search) {
            return array_merge($query, [$arr[$key] => $search[$key]]);
        }, []);
    }

    /**
     * Make the request to the API
     *
     * @param string $method
     * @param string $url
     * @param array $data
     * @return array
     */
    private function request(string $method, string $url, array $data = []): array
    {
        $guzzleClient = new Client();

        try {
            $options = $this->makeRequestOptions($method, $data);
            $options['verify'] = $this->cert_trust_path;
            $response = $guzzleClient->request($method, $url, $options);

            return [
                'success' => true,
                'response_code'  => $response->getStatusCode(),
                'response_body' => json_decode($response->getBody()->getContents(), true),
            ];
        } catch (\Throwable $e) {
            logger()->error($e->getMessage());
            return [
                'success' => false,
                'response_code' => $e->getCode(),
                'response_error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get API endpoints by action
     *
     * @param string $action
     * @param string|null $bsn
     * @param int|null $id
     * @return string
     */
    private function getEndpoint(string $action, string $bsn = null, int $id = null): string
    {
        $endpoints = [
            'children' => "ingeschrevenpersonen/$bsn/kinderen",
            'child' => "ingeschrevenpersonen/$bsn/kinderen/$id",
            'parents' => "ingeschrevenpersonen/$bsn/ouders",
            'parent' => "ingeschrevenpersonen/$bsn/ouders/$id",
            'partners' => "ingeschrevenpersonen/$bsn/partners",
            'partner' => "ingeschrevenpersonen/$bsn/partners/$id",
            'person' => "ingeschrevenpersonen/$bsn",
            'search' => "ingeschrevenpersonen"
        ];

        if (empty($endpoints[$action] ?? null)) {
            throw new RuntimeException('Invalid iConnection action.');
        }

        return $this->api_url . $endpoints[$action];
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
     * @param string $method
     * @param array $data
     * @return array
     */
    private function makeRequestOptions(string $method, array $data): array {
        return [
            'cert' => [Arr::get($this->configs, 'cert_path'), Arr::get($this->configs, 'cert_pass')],
            'ssl_key' => [Arr::get($this->configs, 'key_path'), Arr::get($this->configs, 'key_pass')],
            'headers' => $this->makeRequestHeaders(),
            'connect_timeout' => Arr::get($this->configs, 'connect_timeout', 10),
            $method === 'GET' ? 'query' : 'json' => $data,
        ];
    }

    /**
     * @param string $prefix
     * @param array $query
     * @param callable $callback
     * @return mixed
     */
    protected function remember(string $prefix, array $query, callable $callback): ?array
    {
        return Cache::remember(
            sprintf(self::CACHE_KEY . ".%s-%s", $prefix, http_build_query($query)),
            $this->cache_time,
            $callback
        );
    }

    /**
     * @param array $with
     * @param array $fields
     * @return array
     */
    private function buildQuery(array $with = [], array $fields = []): array
    {
        $with = array_only($this->with, array_keys($with));

        sort($with);
        sort($fields);

        return array_filter([
            'with' => implode(',', count($with) ? $with : []),
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
