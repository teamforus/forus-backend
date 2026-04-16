<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\Organization;
use App\Models\Product;
use App\Models\ProductReservation;
use App\Models\ReservationField;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Throwable;

class ProductReservationCustomFieldValuesTest extends TestCase
{
    use MakesTestFunds;
    use MakesProductReservations;
    use DatabaseTransactions;

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderCanSaveOptionalFileCustomFieldWithoutFilesSelected(): void
    {
        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $provider->reservation_fields()->create([
            'label' => 'provider optional file field',
            'type' => ReservationField::TYPE_FILE,
            'description' => 'provider optional file field description',
            'required' => false,
            'fillable_by' => ReservationField::FILLABLE_BY_PROVIDER,
            'order' => 1,
        ]);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [],
        ]);

        $fieldValue = $reservation->custom_fields()->where('reservation_field_id', $field->id)->first();

        $this->assertNotNull($fieldValue);
        $this->assertNull($fieldValue->value);
        $this->assertSame([], $fieldValue->files()->pluck('uid')->toArray());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderCanClearExistingFileCustomField(): void
    {
        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $provider->reservation_fields()->create([
            'label' => 'provider file field',
            'type' => ReservationField::TYPE_FILE,
            'description' => 'provider file field description',
            'required' => false,
            'fillable_by' => ReservationField::FILLABLE_BY_PROVIDER,
            'order' => 1,
        ]);

        $uploadedFile = $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
            'file' => UploadedFile::fake()->image('existing-file.png'),
        ]);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [$uploadedFile->uid],
        ]);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [],
        ]);

        $fieldValue = $reservation->custom_fields()->firstWhere('reservation_field_id', $field->id);

        $this->assertNotNull($fieldValue);
        $this->assertNull($fieldValue->value);
        $this->assertSame([], $fieldValue->files()->pluck('uid')->toArray());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderCanReplaceExistingFileCustomFieldWithMultipleFiles(): void
    {
        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $provider->reservation_fields()->create([
            'label' => 'provider multi file field',
            'type' => ReservationField::TYPE_FILE,
            'description' => 'provider multi file field description',
            'required' => false,
            'fillable_by' => ReservationField::FILLABLE_BY_PROVIDER,
            'order' => 1,
        ]);

        $uploadedFile = $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
            'file' => UploadedFile::fake()->image('uploaded-file.png'),
        ]);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [$uploadedFile->uid],
        ]);

        $firstFile = $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
            'file' => UploadedFile::fake()->image('replacement-first.png'),
        ]);

        $secondFile = $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
            'file' => UploadedFile::fake()->image('replacement-second.png'),
        ]);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [$firstFile->uid, $secondFile->uid],
        ]);

        $fieldValue = $reservation->custom_fields()->firstWhere('reservation_field_id', $field->id);

        $this->assertNotNull($fieldValue);
        $this->assertNull($fieldValue->value);
        $this->assertSame([$firstFile->uid, $secondFile->uid], $fieldValue->files()->pluck('uid')->toArray());
        $this->assertSame(
            ['replacement-first.png', 'replacement-second.png'],
            $fieldValue->files()->pluck('original_name')->toArray(),
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderCanResubmitAttachedProviderFileCustomFieldValue(): void
    {
        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $provider->reservation_fields()->create([
            'label' => 'provider resubmitted file field',
            'type' => ReservationField::TYPE_FILE,
            'description' => 'provider resubmitted file field description',
            'required' => false,
            'fillable_by' => ReservationField::FILLABLE_BY_PROVIDER,
            'order' => 1,
        ]);

        $uploadedFile = $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
            'file' => UploadedFile::fake()->image('resubmitted-file.png'),
        ]);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [$uploadedFile->uid],
        ]);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [$uploadedFile->uid],
        ]);

        $fieldValue = $reservation->custom_fields()->firstWhere('reservation_field_id', $field->id);

        $this->assertNotNull($fieldValue);
        $this->assertSame([$uploadedFile->uid], $fieldValue->files()->pluck('uid')->toArray());
        $this->assertSame(['resubmitted-file.png'], $fieldValue->files()->pluck('original_name')->toArray());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderRequiresValueForRequiredTextCustomFieldUpdate(): void
    {
        $this->assertProviderRequiresValueForRequiredCustomFieldUpdate(ReservationField::TYPE_TEXT);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderRequiresValueForRequiredBooleanCustomFieldUpdate(): void
    {
        $this->assertProviderRequiresValueForRequiredCustomFieldUpdate(ReservationField::TYPE_BOOLEAN);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterReservationRejectsInvalidBooleanCustomFieldValue(): void
    {
        ['provider' => $provider, 'product' => $product, 'voucher' => $voucher] = $this->makeReservationContext(false);

        $field = $provider->reservation_fields()->create([
            'label' => 'requester boolean field',
            'type' => ReservationField::TYPE_BOOLEAN,
            'description' => 'requester boolean field description',
            'required' => true,
            'fillable_by' => ReservationField::FILLABLE_BY_REQUESTER,
            'order' => 1,
        ]);

        $this->makeReservationStoreRequest($voucher, $product, [
            'custom_fields' => [
                $field->id => 'invalid',
            ],
        ])->assertJsonValidationErrors(["custom_fields.$field->id"]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRequesterReservationRejectsUnsupportedCustomFieldType(): void
    {
        ['provider' => $provider, 'product' => $product, 'voucher' => $voucher] = $this->makeReservationContext(false);

        $field = $provider->reservation_fields()->create([
            'label' => 'unsupported requester field',
            'type' => 'unsupported_type',
            'description' => 'unsupported requester field description',
            'required' => true,
            'fillable_by' => ReservationField::FILLABLE_BY_REQUESTER,
            'order' => 1,
        ]);

        $this->makeReservationStoreRequest($voucher, $product, [
            'custom_fields' => [
                $field->id => 'unsupported value',
            ],
        ])->assertJsonValidationErrors(["custom_fields.$field->id"]);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderSearchFindsReservationByAttachedCustomFieldFileName(): void
    {
        ['fund' => $fund, 'provider' => $provider, 'product' => $product, 'reservation' => $reservation] =
            $this->makeReservationContext();

        $secondVoucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity());
        $secondReservation = $this->makeReservation($secondVoucher, $product);

        $field = $provider->reservation_fields()->create([
            'label' => 'provider searchable file field',
            'type' => ReservationField::TYPE_FILE,
            'description' => 'provider searchable file field description',
            'required' => false,
            'fillable_by' => ReservationField::FILLABLE_BY_PROVIDER,
            'order' => 1,
        ]);

        $this->apiUpdateProductReservationFieldByProvider($provider, $reservation, $field, [
            'value' => [
                $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
                    'file' => UploadedFile::fake()->image('search-first-file.png'),
                ])->uid,
                $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
                    'file' => UploadedFile::fake()->image('search-second-file.png'),
                ])->uid,
            ],
        ]);

        $response = $this->apiGetProductReservationsByProviderRequest($provider, [
            'q' => 'search-second-file.png',
        ]);

        $response->assertSuccessful();

        $this->assertSame([$reservation->id], array_column($response->json('data'), 'id'));
        $this->assertNotSame($reservation->id, $secondReservation->id);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderCannotDestroyRequesterCustomFieldFile(): void
    {
        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $provider->reservation_fields()->create([
            'label' => 'requester protected file field',
            'type' => ReservationField::TYPE_FILE,
            'description' => 'requester protected file field description',
            'required' => false,
            'fillable_by' => ReservationField::FILLABLE_BY_REQUESTER,
            'order' => 1,
        ]);

        $uploadedFile = $this->apiUploadProductReservationCustomFieldFile($provider->identity, [
            'file' => UploadedFile::fake()->image('requester-protected-file.png'),
        ]);

        $fieldValue = $reservation->custom_fields()->create([
            'reservation_field_id' => $field->id,
            'value' => null,
        ]);

        $fieldValue->appendFilesByUid($uploadedFile->uid);
        $uploadedFile = $uploadedFile->refresh();

        $this->assertFalse(Gate::forUser($provider->identity)->allows('destroy', $uploadedFile));
    }

    /**
     * @param string $type
     * @throws Throwable
     * @return void
     */
    protected function assertProviderRequiresValueForRequiredCustomFieldUpdate(string $type): void
    {
        ['provider' => $provider, 'reservation' => $reservation] = $this->makeReservationContext();

        $field = $provider->reservation_fields()->create([
            'label' => "required provider $type field",
            'type' => $type,
            'description' => "required provider $type field description",
            'required' => true,
            'fillable_by' => ReservationField::FILLABLE_BY_PROVIDER,
            'order' => 1,
        ]);

        $this->apiUpdateProductReservationFieldByProviderRequest($provider, $reservation, $field, [
            'value' => null,
        ])->assertJsonValidationErrors(['value']);
    }

    /**
     * @param bool $withReservation
     * @return array{
     *     fund: Fund,
     *     product: Product,
     *     provider: Organization,
     *     reservation: ProductReservation|null,
     *     voucher: Voucher,
     * }
     */
    protected function makeReservationContext(bool $withReservation = true): array
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity($this->makeUniqueEmail()));
        $fund = $this->makeTestFund($sponsor);
        $provider = $this->makeTestProviderOrganization($this->makeIdentity($this->makeUniqueEmail('provider_')));
        $product = $this->createProductForReservation($provider, [$fund]);
        $voucher = $this->makeTestVoucher($fund, identity: $this->makeIdentity($this->makeUniqueEmail()));

        $product->update([
            'reservation_fields_enabled' => true,
            'reservation_fields_config' => Product::CUSTOM_RESERVATION_FIELDS_GLOBAL,
        ]);

        return [
            'fund' => $fund,
            'provider' => $provider,
            'product' => $product,
            'voucher' => $voucher,
            'reservation' => $withReservation ? $this->makeReservation($voucher, $product) : null,
        ];
    }
}
