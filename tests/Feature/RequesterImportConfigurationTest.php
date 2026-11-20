<?php

namespace Tests\Feature;

use App\Modules\RequesterImport\Exceptions\RequesterImportErrorCode;
use App\Modules\RequesterImport\Exceptions\RequesterImportException;
use App\Modules\RequesterImport\Services\RequesterImportRowsService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;
use Tests\Traits\MakesTestRequesterImports;
use Throwable;

class RequesterImportConfigurationTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestRequesterImports;

    /**
     * @throws Throwable
     * @return void
     */
    public function testReturnsConfiguredImportColumnsAndRequiredness(): void
    {
        ['organization' => $organization, 'name' => $name, 'address' => $address] = $this->makeRequesterImportFixture();
        $fields = Config::get('requester_import.mappings.' . $organization->requester_import_mapping_key . '.fields');

        $this->makeRequesterImportFixture();

        $response = $this->apiGetRequesterImportConfigurationRequest($organization, $organization->identity)
            ->assertOk()
            ->assertExactJson([
                'data' => ['key' => $organization->requester_import_mapping_key, 'columns' => $fields],
            ]);

        $this->assertSame(array_keys($fields), array_keys($response->json('data.columns')));
        $this->assertTrue($response->json('data.columns.' . $name->key . '.required'));
        $this->assertFalse($response->json('data.columns.' . $address->key . '.required'));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectsUnavailableMappingConfiguration(): void
    {
        ['organization' => $organization, 'address' => $address] = $this->makeRequesterImportFixture();
        ['address' => $foreignType] = $this->makeRequesterImportFixture();
        $mappingKey = $organization->requester_import_mapping_key;
        $path = 'requester_import.mappings.' . $mappingKey;
        $preset = Config::get($path);
        $address->update(['requester_imports' => false]);

        $variants = [
            'missing preset' => [null, RequesterImportErrorCode::MAPPING_UNAVAILABLE],
            'disabled type' => [$preset, RequesterImportErrorCode::MAPPING_RECORDS_UNAVAILABLE],
            'foreign type' => [['fields' => [
                'bsn' => ['column' => 'BSN'],
                $foreignType->key => ['column' => 'Foreign'],
            ]], RequesterImportErrorCode::MAPPING_RECORDS_UNAVAILABLE],
            'duplicate header' => [['fields' => [
                'bsn' => ['column' => 'Same'],
                'client_number' => ['column' => 'Same'],
            ]], RequesterImportErrorCode::MAPPING_COLUMNS_AMBIGUOUS],
        ];

        foreach ($variants as $variant => [$configuration, $code]) {
            Config::set($path, $configuration);

            try {
                resolve(RequesterImportRowsService::class)->getConfiguration($organization);
                $this->fail('Expected configuration rejection: ' . $variant);
            } catch (RequesterImportException $exception) {
                $this->assertSame($code, $exception->errorCode(), $variant);
            }

            $this->apiGetRequesterImportConfigurationRequest($organization, $organization->identity)
                ->assertUnprocessable()->assertJsonValidationErrors('requester_import_mapping_key')
                ->assertJsonMissingPath('data');
        }
    }
}
