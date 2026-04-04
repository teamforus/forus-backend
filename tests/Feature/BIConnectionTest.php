<?php

namespace Tests\Feature;

use App\Exports\EmployeesExport;
use App\Models\Organization;
use App\Services\BIConnectionService\BIConnectionService;
use App\Services\BIConnectionService\Models\BIConnection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Tests\Feature\Exports\Stubs\StubBIExporter;
use Tests\TestCase;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestVouchers;
use Throwable;

class BIConnectionTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesTestFunds;
    use MakesTestVouchers;

    /**
     * @var string
     */
    protected string $apiUrl = '/api/v1/bi/export';

    /**
     * @var string
     */
    protected string $apiOrganizationUrl = '/api/v1/platform/organizations/%s/bi-connection';

    /**
     * @throws Throwable
     * @return void
     */
    public function testValidTokenWhenEnabled(): void
    {
        $this->testValidToken();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testAuthTypeDisabled(): void
    {
        $this->testValidToken(false);
    }

    /**
     * @return void
     */
    public function testWithoutToken(): void
    {
        $this->getJson($this->apiUrl)->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testBiExportReturnsLabeledRows(): void
    {
        $ip = '192.168.0.1';
        $this->serverVariables = ['REMOTE_ADDR' => $ip];

        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity, [
            'allow_bi_connection' => true,
        ]);

        $response = $this->getBiExportResponse(
            $this->createBiConnectionToken($organization, $ip, ['employees']),
        );

        $response->assertSuccessful();

        $employeeRows = $response->json('Medewerkers');

        $this->assertNotEmpty($employeeRows);
        $this->assertFalse(array_is_list($employeeRows[0]));
        $this->assertArrayHasKey(EmployeesExport::trans('email'), $employeeRows[0]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testBiExportReturnsLabeledRowsForAllConfiguredDataTypes(): void
    {
        $ip = '192.168.0.1';
        $this->serverVariables = ['REMOTE_ADDR' => $ip];

        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity, [
            'allow_bi_connection' => true,
            'bsn_enabled' => true,
        ]);
        $fund = $this->makeTestFund($organization, fundConfigsData: [
            'allow_voucher_records' => false,
        ]);

        $this->makeTestVoucher($fund, $this->makeIdentity($this->makeUniqueEmail()));

        $dataTypes = Arr::pluck(BIConnectionService::create($organization)->getDataTypes(), 'key');
        $response = $this->getBiExportResponse($this->createBiConnectionToken($organization, $ip, $dataTypes));

        $response->assertSuccessful();

        foreach ($response->json() as $rows) {
            $this->assertIsArray($rows);

            if (empty($rows)) {
                continue;
            }

            $this->assertFalse(array_is_list($rows[0]));
            $this->assertNotEmpty(array_keys($rows[0]));
        }
    }

    /**
     * @return void
     */
    public function testBiExporterMakesDuplicateLabelsUniqueWhenEnabled(): void
    {
        $duplicateLabel = 'duplicate role';
        $rows = $this->makeStubBiExporter(true)->transformRows(
            [$duplicateLabel, $duplicateLabel],
            [[1, 2]],
        );

        $this->assertSame(1, $rows[0][$duplicateLabel] ?? null);
        $this->assertSame(2, $rows[0]["$duplicateLabel (2)"] ?? null);
    }

    /**
     * @return void
     */
    public function testBiExporterMakesDuplicateLabelsUniqueWhenLabelsAlreadyContainSuffixes(): void
    {
        $rows = $this->makeStubBiExporter(true)->transformRows(
            ['duplicate role', 'duplicate role (2)', 'duplicate role'],
            [[1, 2, 3]],
        );

        $this->assertSame(1, $rows[0]['duplicate role'] ?? null);
        $this->assertSame(2, $rows[0]['duplicate role (2)'] ?? null);
        $this->assertSame(3, $rows[0]['duplicate role (3)'] ?? null);
    }

    /**
     * @return void
     */
    public function testBiExporterKeepsDuplicateLabelsUnchangedWhenDisabled(): void
    {
        $duplicateLabel = 'duplicate role';
        $rows = $this->makeStubBiExporter(false)->transformRows(
            [$duplicateLabel, $duplicateLabel],
            [[1, 2]],
        );

        $this->assertSame(['duplicate role' => 2], $rows[0]);
    }

    /**
     * @param bool $enabled
     * @throws Throwable
     * @return void
     */
    protected function testValidToken(bool $enabled = true): void
    {
        $ip = '192.168.0.1';
        $this->serverVariables = ['REMOTE_ADDR' => $ip];

        /** @var Organization $organization */

        $identity = $this->makeIdentity();
        $organization = $this->makeTestOrganization($identity, [
            'allow_bi_connection' => true,
        ]);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity), [
            'Client-Type' => 'sponsor',
        ]);

        $this->assertNotNull($organization);

        $response = $this->postJson(sprintf($this->apiOrganizationUrl, $organization->id), [
            'ips' => [$ip],
            'enabled' => $enabled,
            'data_types' => Arr::pluck(BIConnectionService::create($organization)->getDataTypes(), 'key'),
            'expiration_period' => BIConnection::EXPIRATION_PERIODS[0],
        ], $apiHeaders);

        $response->assertSuccessful();
        $response->assertJsonStructure(['data' => ['access_token']]);

        $token = $response->json('data.access_token');

        if ($enabled) {
            $this->getJson($this->apiUrl, [
                BIConnection::AUTH_TYPE_HEADER_NAME => $token,
            ])->assertSuccessful();
        } else {
            $this->getJson($this->apiUrl, [
                BIConnection::AUTH_TYPE_HEADER_NAME => $token,
            ])->assertForbidden();
        }

        $this->getJson($this->apiUrl)->assertForbidden();
    }

    /**
     * @param Organization $organization
     * @param string $ip
     * @param array $dataTypes
     * @return string
     */
    protected function createBiConnectionToken(Organization $organization, string $ip, array $dataTypes): string
    {
        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($organization->identity), [
            'Client-Type' => 'sponsor',
        ]);

        $response = $this->postJson(sprintf($this->apiOrganizationUrl, $organization->id), [
            'ips' => [$ip],
            'enabled' => true,
            'data_types' => $dataTypes,
            'expiration_period' => BIConnection::EXPIRATION_PERIODS[0],
        ], $apiHeaders);

        $response->assertSuccessful();

        return $response->json('data.access_token');
    }

    /**
     * @param string $token
     * @return TestResponse
     */
    protected function getBiExportResponse(string $token): TestResponse
    {
        return $this->getJson($this->apiUrl, [
            BIConnection::AUTH_TYPE_HEADER_NAME => $token,
        ]);
    }

    /**
     * @param bool $makeExportRowsUnique
     * @return StubBIExporter
     */
    protected function makeStubBiExporter(bool $makeExportRowsUnique): StubBIExporter
    {
        return new StubBIExporter($this->makeTestOrganization($this->makeIdentity()), $makeExportRowsUnique);
    }
}
