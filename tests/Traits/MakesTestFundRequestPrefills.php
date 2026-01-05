<?php

namespace Tests\Traits;

use App\Models\Fund;
use App\Models\Identity;
use App\Models\Organization;
use App\Models\PersonBsnApiRecordType;
use App\Models\RecordType;
use App\Services\IConnectApiService\IConnect;
use App\Services\IConnectApiService\IConnectPrefill;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;

trait MakesTestFundRequestPrefills
{
    /**
     * @param Organization $organization
     * @return void
     */
    protected function enablePersonBsnApiForOrganization(Organization $organization): void
    {
        $organization->forceFill([
            'bsn_enabled' => true,
            'iconnect_env' => 'sandbox',
            'iconnect_key' => 'test',
            'iconnect_cert' => 'test',
            'iconnect_cert_trust' => 'test',
            'iconnect_api_oin' => 'test-oin',
            'iconnect_target_binding' => 'test-binding',
            'iconnect_base_url' => IConnect::URL_SANDBOX,
            'iconnect_key_pass' => '',
            'iconnect_cert_pass' => '',
        ])->save();
    }

    /**
     * @param Organization $organization
     * @param string $key
     * @param string $type
     * @param string $controlType
     * @return RecordType
     */
    protected function makeRecordTypeForKey(
        Organization $organization,
        string $key,
        string $type,
        string $controlType,
    ): RecordType {
        $existing = RecordType::where('key', $key)->first();

        if ($existing) {
            return $existing;
        }

        return RecordType::create([
            'key' => $key,
            'type' => $type,
            'criteria' => true,
            'control_type' => $controlType,
            'organization_id' => $organization->id,
        ]);
    }

    /**
     * @param Organization $organization
     * @param string $key
     * @param string $personField
     * @return RecordType
     */
    protected function makePrefillRecordType(
        Organization $organization,
        string $key,
        string $personField,
    ): RecordType {
        $recordType = $this->makeRecordTypeForKey(
            $organization,
            $key,
            RecordType::TYPE_STRING,
            RecordType::CONTROL_TYPE_TEXT,
        );

        PersonBsnApiRecordType::create([
            'person_bsn_api_field' => $personField,
            'record_type_key' => $recordType->key,
        ]);

        return $recordType;
    }

    /**
     * @param array $overrides
     * @return void
     */
    protected function fakePersonBsnApiResponses(array $overrides = []): void
    {
        $defaultData = Config::get('forus.person_bsn.test_response_data', []);

        Http::fake([
            Str::finish(IConnect::URL_SANDBOX, '/') . '*' => function (Request $request) use ($defaultData, $overrides) {
                $url = parse_url($request->url());
                $segments = explode('/', trim($url['path'], '/'));
                $bsn = last($segments);

                if (array_key_exists($bsn, $overrides)) {
                    $override = $overrides[$bsn];

                    return Http::response(
                        $override['body'] ?? $override['data'] ?? [],
                        $override['status'] ?? 200
                    );
                }

                return Http::response($defaultData[$bsn] ?? []);
            },
        ]);
    }

    /**
     * @param Identity $identity
     * @param Fund $fund
     * @param array $records
     * @param bool $validate
     * @param string $bsn
     * @param array $headers
     * @param array $data
     * @return TestResponse
     */
    protected function makeFundRequestWithBsn(
        Identity $identity,
        Fund $fund,
        array $records,
        bool $validate,
        string $bsn,
        array $headers = [],
        array $data = [],
    ): TestResponse {
        $url = "/api/v1/platform/funds/$fund->id/requests" . ($validate ? '/validate' : '');
        $proxy = $this->makeIdentityProxy($identity);

        $identity->setBsnRecord($bsn);

        return $this->postJson($url, [...compact('records'), ...$data], $this->makeApiHeaders($proxy, $headers));
    }

    /**
     * @param Fund $fund
     * @param string $bsn
     * @return void
     */
    protected function forgetFundPrefillCache(Fund $fund, string $bsn): void
    {
        IConnectPrefill::forgetBsnApiPrefills($fund, $bsn);
    }
}
