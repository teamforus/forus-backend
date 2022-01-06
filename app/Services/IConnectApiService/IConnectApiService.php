<?php


namespace App\Services\IConnectApiService;

use App\Models\Organization;
use App\Services\IConnectApiService\Responses\Child;
use App\Services\IConnectApiService\Responses\ParentPerson;
use App\Services\IConnectApiService\Responses\Partner;
use App\Services\IConnectApiService\Responses\Person;
use GuzzleHttp\Client;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class IConnectApiService
 * @package App\Services\IConnectApiService
 */
class IConnectApiService
{
    /** @var string  */
    private const METHOD_GET = 'GET';
    /** @var string  */
    private static $cache_key_prefix = 'iconnect_';

    /** @var string  */
    private $api_url = 'https://apitest.locgov.nl/iconnect/brpmks/1.3.0/';

    /** @var string[]  */
    private $with = [
        'parents' => 'ouders',
        'children' => 'kinderen',
        'partners' => 'partners'
    ];

    /** @var string|null  */
    private $person_bsn_api_id;

    /**
     * @param string $personBsnApiId
     */
    public function __construct(string $personBsnApiId)
    {
        $this->person_bsn_api_id = $personBsnApiId;
    }

    /**
     * @param string $bsn
     * @return Child[]
     */
    public function getChildren(string $bsn): array
    {
        $url = $this->getEndpoint('children', $bsn);
        $result = $this->request(self::METHOD_GET, $url);

        if ($result['success']) {
            $arr = [];
            foreach ($result['response_body']['_embedded'] ?? [] as $item) {
                $arr[] = new Child($item);
            }

            return $arr;
        }

        return [];
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

        if ($result['success']) {
            $arr = [];
            foreach ($result['response_body']['_embedded'] ?? [] as $item) {
                $arr[] = new ParentPerson($item);
            }

            return $arr;
        }

        return [];
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
     * @return Partner|null
     */
    public function getPartners(string $bsn): ?Partner
    {
        $url = $this->getEndpoint('partners', $bsn);
        $result = $this->request(self::METHOD_GET, $url);

        return $result['success'] ? new Partner($result['response_body']['_embedded'] ?? []) : null;
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
     * @throws \Exception
     */
    public function getPerson(string $bsn, array $with = [], array $fields = []): ?Person
    {
        $url = $this->getEndpoint('person', $bsn);

        $query = [];
        $with = array_intersect_key($this->with, array_flip($with));
        if (count($with)) {
            $query['expand'] = implode(',', $with);
        }

        if (count($fields)) {
            $query['fields'] = implode(',', $fields);
        }

        $result = cache()->remember(
            self::$cache_key_prefix . 'person_' . $bsn,
            config('iconnect_api.cache_time') * 60,
            function() use ($url, $query) {
                $request = $this->request(self::METHOD_GET, $url, $query);

                return $request['success'] ? $request['response_body'] : null;
            }
        );

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

        $query = [];
        $with = array_intersect_key($this->with, array_flip($with));
        if (count($with)) {
            $query['expand'] = implode(',', $with);
        }

        if (count($fields)) {
            $query['fields'] = implode(',', $fields);
        }

        $query = array_merge($query, $this->getSearchParams($search));
        $result = $this->request(self::METHOD_GET, $url, $query);

        if ($result['success']) {
            $arr = [];
            foreach ($result['response_body']['_embedded']['ingeschrevenpersonen'] ?? [] as $item) {
                $arr[] = new Person($item);
            }

            return $arr;
        }

        return [];
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

        $result = [];
        array_walk($search, static function($value, $key) use ($arr, &$result) {
            $result[$arr[$key] ?? ''] = $value;
        });

        return $result;
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
            $options['verify'] = config('iconnect_api.cert_trust_pass');
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
        $endpoint = [
                'children' => "ingeschrevenpersonen/$bsn/kinderen",
                'child' => "ingeschrevenpersonen/$bsn/kinderen/$id",
                'parents' => "ingeschrevenpersonen/$bsn/ouders",
                'parent' => "ingeschrevenpersonen/$bsn/ouders/$id",
                'partners' => "ingeschrevenpersonen/$bsn/partners",
                'partner' => "ingeschrevenpersonen/$bsn/partners/$id",
                'person' => "ingeschrevenpersonen/$bsn",
                'search' => "ingeschrevenpersonen"
            ][$action] ?? abort(403);

        return $this->api_url . $endpoint;
    }

    /**
     * Make request headers
     *
     * @return string[]
     */
    private function makeRequestHeaders(): array
    {
        return [
            'apikey' => config('iconnect_api.header_key'),
            'x-doelbinding' => config('iconnect_api.target_binding'),
            'x-origin-oin' => $this->person_bsn_api_id,
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
        return array_merge([
            'headers' => $this->makeRequestHeaders(),
            'connect_timeout' => config('iconnect_api.connect_timeout', 10),
            'cert' => [
                config('iconnect_api.cert_path'),
                config('iconnect_api.cert_pass')
            ],
            'ssl_key' => [
                config('iconnect_api.key_path'),
                config('iconnect_api.key_pass')
            ],
        ], $method === 'GET' ? [
            'query' => $data,
        ]: [
            'json' => $data,
        ]);
    }

}
