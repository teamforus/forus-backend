<?php

namespace Tests\Feature\Exports;

use App\Exports\ProductReservationsExport;
use App\Models\ProductReservation;
use App\Models\ProductReservationFieldValue;
use App\Models\ReservationField;
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
        $this->assertFields($response, $reservation, $fields);

        // Assert with passed all fields
        $url = sprintf($this->apiExportUrl, $provider->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ProductReservationsExport::getExportFieldsRaw(),
        ]);

        $response = $this->getJson($url, $apiHeaders);
        $this->assertFields($response, $reservation, $fields);

        // Assert specific fields
        $url = sprintf($this->apiExportUrl, $provider->id) . '?' . http_build_query([
            'data_format' => 'csv',
            'fields' => ['code'],
        ]);

        $response = $this->getJson($url, $apiHeaders);

        $this->assertFields($response, $reservation, [
            ProductReservationsExport::trans('code'),
        ]);
    }

    /**
     * @param ProductReservation $reservation
     * @return array
     */
    protected function getExportFields(ProductReservation $reservation): array
    {
        $fields = Arr::pluck(ProductReservationsExport::getExportFields(), 'name');

        $fields = array_filter($fields, fn ($field) => $field !== ProductReservationsExport::trans('records'));

        $fieldIds = ProductReservationFieldValue::query()
            ->where('product_reservation_id', $reservation->id)
            ->pluck('reservation_field_id')
            ->toArray();

        $fieldList = ReservationField::query()->whereIn('id', $fieldIds)->pluck('label', 'id');

        return [...$fields, ...$fieldList];
    }

    /**
     * @param TestResponse $response
     * @param ProductReservation $reservation
     * @param array $fields
     * @return void
     */
    protected function assertFields(
        TestResponse $response,
        ProductReservation $reservation,
        array $fields,
    ): void {
        $response->assertStatus(200);
        $response->assertDownload();

        $rows = $this->getCsvData($response);

        // Assert that the first row (header) contains expected columns
        $this->assertEquals($fields, $rows[0]);

        // Assert specific fields
        $this->assertEquals($reservation->code, $rows[1][0]);
    }
}
