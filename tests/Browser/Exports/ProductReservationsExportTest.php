<?php

namespace Tests\Browser\Exports;

use App\Exports\ProductReservationsExport;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\ProductReservation;
use App\Models\ProductReservationFieldValue;
use App\Models\ReservationField;
use Exception;
use Illuminate\Support\Arr;
use Laravel\Dusk\Browser;
use Tests\Browser\Traits\ExportTrait;
use Tests\Browser\Traits\HasFrontendActions;
use Tests\Browser\Traits\NavigatesFrontendDashboard;
use Tests\Browser\Traits\RollbackModelsTrait;
use Tests\DuskTestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Throwable;

class ProductReservationsExportTest extends DuskTestCase
{
    use ExportTrait;
    use MakesTestFunds;
    use HasFrontendActions;
    use RollbackModelsTrait;
    use MakesProductReservations;
    use NavigatesFrontendDashboard;

    /**
     * @throws Throwable
     * @return void
     */
    public function testProductReservationsExport(): void
    {
        $implementation = Implementation::byKey('nijmegen');

        $fund = $this->makeTestFund($implementation->organization);
        $reservation = $this->prepareData($fund);

        $organization = $reservation->product->organization;
        $this->assertNotNull($organization);

        $this->rollbackModels([], function () use ($implementation, $organization, $reservation) {
            $this->browse(function (Browser $browser) use ($implementation, $organization, $reservation) {
                $browser->visit($implementation->urlProviderDashboard());

                // Authorize identity
                $this->loginIdentity($browser, $organization->identity);
                $this->assertIdentityAuthenticatedOnProviderDashboard($browser, $organization->identity);
                $this->selectDashboardOrganization($browser, $organization);

                // Go to list, open export modal and assert all export fields in file
                $this->goToReservationsPage($browser);
                $this->searchTable($browser, '@tableReservation', $reservation->first_name, $reservation->id);

                $fields = $this->getExportFields($reservation);

                foreach (static::FORMATS as $format) {
                    // assert all fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format);
                    $data && $this->assertExportedData($reservation, $data, $fields);

                    // assert specific fields exported
                    $this->openFilterDropdown($browser);
                    $data = $this->fillExportModalAndDownloadFile($browser, $format, ['code']);

                    $data && $this->assertExportedData($reservation, $data, [
                        ProductReservationsExport::trans('code'),
                    ]);
                }

                // Logout
                $this->logout($browser);
            });
        }, function () use ($fund, $reservation) {
            $reservation && $reservation->delete();
            $fund && $this->deleteFund($fund);
        });
    }

    /**
     * @param Fund $fund
     * @throws Exception
     * @return ProductReservation
     */
    protected function prepareData(Fund $fund): ProductReservation
    {
        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity(), amount: 1000);

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
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'custom_fields' => [$field->id => 'custom field'],
        ]);

        // add custom field filled by provider
        $reservation->custom_fields()->create([
            'reservation_field_id' => $providerField->id,
            'value' => 'custom provider field',
        ]);

        $this->assertNotNull($reservation);

        return $reservation;
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

        $fieldList = ReservationField::query()->whereIn('id', $fieldIds)->pluck('label', 'id');

        return [...$fields, ...$fieldList];
    }

    /**
     * @param ProductReservation $reservation
     * @param array $rows
     * @param array $fields
     * @return void
     */
    protected function assertExportedData(
        ProductReservation $reservation,
        array $rows,
        array $fields
    ): void {
        $this->assertExportHeaders($rows, $fields);
        $this->assertExportCell($rows, $reservation->code, 0);
    }
}
