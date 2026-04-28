<?php

namespace Tests\Feature\Exports;

use App\Exports\ProductReservationsExport;
use App\Models\ProductReservation;
use App\Models\ProductReservationFieldValue;
use App\Models\ReservationField;
use App\Services\FileService\Models\File;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\BaseExport;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class ProductReservationsExportTest extends TestCase
{
    use BaseExport;
    use MakesTestFunds;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesProductReservations;

    /**
     * @var string
     */
    protected string $apiExportUrl = '/api/v1/platform/organizations/%s/product-reservations/export';

    /**
     * @var string
     */
    protected string $apiExportFieldsUrl = '/api/v1/platform/organizations/%s/product-reservations/export-fields';

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationsExport(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($sponsorOrganization);
        $this->makeProviderAndProducts($fund, 1);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());

        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->createProductForReservation($provider, [$fund]);

        // update product configs
        $product->update([
            'reservation_fields_enabled' => true,
            'reservation_fields_config' => $product::CUSTOM_RESERVATION_FIELDS_GLOBAL,
        ]);

        // add reservation fields fillable by provider and requester
        $field = $provider->reservation_fields()->create([
            'label' => 'organization custom field text',
            'type' => ReservationField::TYPE_TEXT,
            'description' => 'organization custom field text description',
            'required' => true,
            'fillable_by' => ReservationField::FILLABLE_BY_REQUESTER,
            'order' => 0,
        ]);

        $providerField = $provider->reservation_fields()->create([
            'label' => 'organization custom field text by provider',
            'type' => ReservationField::TYPE_TEXT,
            'description' => 'organization custom field text description',
            'required' => false,
            'fillable_by' => ReservationField::FILLABLE_BY_PROVIDER,
            'order' => 0,
        ]);

        // create reservation with custom field
        $reservation = $this->makeReservation($voucher, $product, [
            'custom_fields' => [$field->id => 'custom field'],
        ]);

        // add custom field filled by provider
        $reservation->custom_fields()->create([
            'reservation_field_id' => $providerField->id,
            'value' => 'custom provider field',
        ]);

        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));

        // Assert export without fields - must be all fields by default
        $response = $this->getJson(
            sprintf($this->apiExportUrl, $provider->id) . '?data_format=csv',
            $apiHeaders
        );

        $fields = $this->getExportFields($reservation);
        $this->assertExportedData($response, $reservation, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $provider->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ProductReservationsExport::getExportFieldsRaw($provider),
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertExportedData($response, $reservation, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $provider->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ['code'],
        ]);

        $response = $this->getJson($url, $apiHeaders);

        $this->assertExportedData($response, $reservation, [
            ProductReservationsExport::trans('code'),
        ]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationsExportHandlesExtraAmountPermission(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($sponsorOrganization);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->createProductForReservation($provider, [$fund]);
        $reservation = $this->makeReservation($voucher, $product);

        $reservation->update(['amount_extra' => 7.5]);

        // Assert extra amount field hidden without permission
        $apiHeaders = $this->makeApiHeaders($this->makeIdentityProxy($provider->identity));
        $response = $this->getJson(sprintf($this->apiExportFieldsUrl, $provider->id), $apiHeaders);

        $response->assertStatus(200);
        $this->assertNotContains('amount_extra', Arr::pluck($response->json('data'), 'key'));

        // Assert extra amount field rejected without permission
        $url = sprintf($this->apiExportUrl, $provider->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ['amount_extra'],
        ]);

        $this->getJson($url, $apiHeaders)->assertJsonValidationErrors('fields.0');

        // Enable extra amount permission
        $product->fund_providers()->where('fund_id', $fund->id)->first()->update(['allow_extra_payments' => true]);

        // Assert extra amount field available with permission
        $response = $this->getJson(sprintf($this->apiExportFieldsUrl, $provider->id), $apiHeaders);

        $response->assertStatus(200);
        $this->assertContains('amount_extra', Arr::pluck($response->json('data'), 'key'));

        // Assert extra amount field exported with permission
        $response = $this->getJson(sprintf($this->apiExportUrl, $provider->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ['amount_extra'],
        ]), $apiHeaders);

        $rows = $this->assertCsvExportResponse($response);

        $this->assertExportHeaders($rows, [ProductReservationsExport::trans('amount_extra')]);
        $this->assertEquals((float) $reservation->amount_extra, (float) $rows[1][0]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationsExportKeepsColumnsWithSameVisibleLabel(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($sponsorOrganization);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->createProductForReservation($provider, [$fund]);

        $product->update([
            'reservation_fields_enabled' => true,
            'reservation_fields_config' => $product::CUSTOM_RESERVATION_FIELDS_GLOBAL,
        ]);

        $emailLabel = ProductReservationsExport::trans('email');
        $customValue = 'custom email field value';

        $field = $provider->reservation_fields()->create([
            'label' => $emailLabel,
            'type' => ReservationField::TYPE_TEXT,
            'description' => 'organization custom field text description',
            'required' => true,
            'fillable_by' => ReservationField::FILLABLE_BY_REQUESTER,
            'order' => 0,
        ]);

        $reservation = $this->makeReservation($voucher, $product, [
            'custom_fields' => [$field->id => $customValue],
        ]);

        $response = $this->getJson(
            sprintf($this->apiExportUrl, $provider->id) . '?data_format=csv',
            $this->makeApiHeaders($this->makeIdentityProxy($provider->identity)),
        );

        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);
        $indexes = array_keys($rows[0], $emailLabel, true);

        $this->assertCount(2, $indexes);
        $this->assertEquals($reservation->voucher->identity?->email, $rows[1][$indexes[0]]);
        $this->assertEquals($customValue, $rows[1][$indexes[1]]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationsExportKeepsDeletedCustomFieldColumns(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($sponsorOrganization);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->createProductForReservation($provider, [$fund]);

        $product->update([
            'reservation_fields_enabled' => true,
            'reservation_fields_config' => $product::CUSTOM_RESERVATION_FIELDS_GLOBAL,
        ]);

        $deletedFieldLabel = 'deleted reservation field';
        $deletedFieldValue = 'historical field value';

        $field = $provider->reservation_fields()->create([
            'label' => $deletedFieldLabel,
            'type' => ReservationField::TYPE_TEXT,
            'description' => 'organization custom field text description',
            'required' => true,
            'fillable_by' => ReservationField::FILLABLE_BY_REQUESTER,
            'order' => 0,
        ]);

        $reservation = $this->makeReservation($voucher, $product, [
            'custom_fields' => [$field->id => $deletedFieldValue],
        ]);

        $field->delete();

        $response = $this->getJson(
            sprintf($this->apiExportUrl, $provider->id) . '?data_format=csv',
            $this->makeApiHeaders($this->makeIdentityProxy($provider->identity)),
        );

        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);
        $index = array_search($deletedFieldLabel, $rows[0], true);

        $this->assertNotFalse($index);
        $this->assertEquals($deletedFieldValue, $rows[1][$index]);
        $this->assertNotNull($reservation->custom_fields()->firstWhere('reservation_field_id', $field->id));
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationsExportKeepsConfiguredCustomFieldOrder(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($sponsorOrganization);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->createProductForReservation($provider, [$fund]);

        $product->update([
            'reservation_fields_enabled' => true,
            'reservation_fields_config' => $product::CUSTOM_RESERVATION_FIELDS_GLOBAL,
        ]);

        $secondField = $provider->reservation_fields()->create([
            'label' => 'second field',
            'type' => ReservationField::TYPE_TEXT,
            'description' => 'second field description',
            'required' => true,
            'fillable_by' => ReservationField::FILLABLE_BY_REQUESTER,
            'order' => 2,
        ]);

        $firstField = $provider->reservation_fields()->create([
            'label' => 'first field',
            'type' => ReservationField::TYPE_TEXT,
            'description' => 'first field description',
            'required' => true,
            'fillable_by' => ReservationField::FILLABLE_BY_REQUESTER,
            'order' => 1,
        ]);

        $reservation = $this->makeReservation($voucher, $product, [
            'custom_fields' => [
                $firstField->id => 'first value',
                $secondField->id => 'second value',
            ],
        ]);

        $response = $this->getJson(
            sprintf($this->apiExportUrl, $provider->id) . '?data_format=csv',
            $this->makeApiHeaders($this->makeIdentityProxy($provider->identity)),
        );

        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);
        $indexes = array_map(fn (string $label) => array_search($label, $rows[0], true), ['first field', 'second field']);

        $this->assertNotFalse($indexes[0]);
        $this->assertNotFalse($indexes[1]);
        $this->assertLessThan($indexes[1], $indexes[0]);
        $this->assertEquals('first value', $rows[1][$indexes[0]]);
        $this->assertEquals('second value', $rows[1][$indexes[1]]);
        $this->assertEquals($reservation->code, $rows[1][0]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationsExportUsesTextValueForNonFileFieldEvenWhenFileIsAttached(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($sponsorOrganization);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->createProductForReservation($provider, [$fund]);

        $product->update([
            'reservation_fields_enabled' => true,
            'reservation_fields_config' => $product::CUSTOM_RESERVATION_FIELDS_GLOBAL,
        ]);

        $field = $provider->reservation_fields()->create([
            'label' => 'text field with attachment',
            'type' => ReservationField::TYPE_TEXT,
            'description' => 'text field description',
            'required' => false,
            'fillable_by' => ReservationField::FILLABLE_BY_REQUESTER,
            'order' => 1,
        ]);

        $reservation = $this->makeReservation($voucher, $product);
        $fieldValue = $reservation->custom_fields()->firstWhere('reservation_field_id', $field->id);

        $this->assertNotNull($fieldValue);

        $fieldValue->update([
            'value' => 'text export value',
        ]);

        $file = File::create([
            'identity_address' => $provider->identity_address,
            'original_name' => 'attached-file.pdf',
            'ext' => 'pdf',
            'uid' => File::makeUid(),
            'path' => 'product-reservation-export-test.pdf',
            'size' => '1',
            'type' => 'product_reservation_custom_field',
            'order' => 0,
        ]);

        $fieldValue->appendFilesByUid($file->uid);

        $response = $this->getJson(
            sprintf($this->apiExportUrl, $provider->id) . '?data_format=csv',
            $this->makeApiHeaders($this->makeIdentityProxy($provider->identity)),
        );

        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);
        $index = array_search($field->label, $rows[0], true);

        $this->assertNotFalse($index);
        $this->assertEquals('text export value', $rows[1][$index]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationsExportJoinsAttachedFileNamesForFileField(): void
    {
        $identity = $this->makeIdentity($this->makeUniqueEmail());
        $sponsorOrganization = $this->makeTestOrganization($identity);
        $fund = $this->makeTestFund($sponsorOrganization);

        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->createProductForReservation($provider, [$fund]);

        $product->update([
            'reservation_fields_enabled' => true,
            'reservation_fields_config' => $product::CUSTOM_RESERVATION_FIELDS_GLOBAL,
        ]);

        $field = $provider->reservation_fields()->create([
            'label' => 'file field',
            'type' => ReservationField::TYPE_FILE,
            'description' => 'file field description',
            'required' => false,
            'fillable_by' => ReservationField::FILLABLE_BY_REQUESTER,
            'order' => 1,
        ]);

        $reservation = $this->makeReservation($voucher, $product);
        $fieldValue = $reservation->custom_fields()->firstWhere('reservation_field_id', $field->id);

        $this->assertNotNull($fieldValue);

        $firstFile = File::create([
            'identity_address' => $provider->identity_address,
            'original_name' => 'first-file.pdf',
            'ext' => 'pdf',
            'uid' => File::makeUid(),
            'path' => 'product-reservation-first-file.pdf',
            'size' => '1',
            'type' => 'product_reservation_custom_field',
            'order' => 0,
        ]);

        $secondFile = File::create([
            'identity_address' => $provider->identity_address,
            'original_name' => 'second-file.pdf',
            'ext' => 'pdf',
            'uid' => File::makeUid(),
            'path' => 'product-reservation-second-file.pdf',
            'size' => '1',
            'type' => 'product_reservation_custom_field',
            'order' => 0,
        ]);

        $fieldValue->appendFilesByUid([$firstFile->uid, $secondFile->uid]);

        $response = $this->getJson(
            sprintf($this->apiExportUrl, $provider->id) . '?data_format=csv',
            $this->makeApiHeaders($this->makeIdentityProxy($provider->identity)),
        );

        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);
        $index = array_search($field->label, $rows[0], true);

        $this->assertNotFalse($index);
        $this->assertEquals('first-file.pdf, second-file.pdf', $rows[1][$index]);
    }

    /**
     * @param ProductReservation $reservation
     * @return array
     */
    protected function getExportFields(ProductReservation $reservation): array
    {
        $fields = Arr::pluck(ProductReservationsExport::getExportFields($reservation->product->organization), 'name');

        $fields = array_filter($fields, fn ($field) => $field !== ProductReservationsExport::trans('records'));

        $fieldIds = ProductReservationFieldValue::query()
            ->where('product_reservation_id', $reservation->id)
            ->pluck('reservation_field_id')
            ->toArray();

        $fieldList = ReservationField::withTrashed()
            ->whereIn('id', $fieldIds)
            ->orderBy('order')
            ->orderBy('id')
            ->pluck('label', 'id');

        return [...$fields, ...$fieldList];
    }

    /**
     * @param TestResponse $response
     * @param ProductReservation $reservation
     * @param array $fields
     * @return void
     */
    protected function assertExportedData(
        TestResponse $response,
        ProductReservation $reservation,
        array $fields,
    ): void {
        $rows = $this->assertCsvExportResponse($response);

        $this->assertExportHeaders($rows, $fields);
        $this->assertExportCell($rows, $reservation->code, 0);
    }
}
