<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Product;
use App\Models\ReservationField;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestProducts;
use Throwable;

class ProductReservationFieldsConfigTest extends TestCase
{
    use MakesTestProducts;
    use DatabaseTransactions;
    use MakesTestOrganizations;
    use MakesTestFunds;
    use MakesProductReservations;

    /**
     * @var string[]
     */
    protected $defaultHeaders = [
        'Accept' => 'application/json',
        'Client-Type' => 'provider',
    ];

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdateProductReservationFieldsConfigSuccess(): void
    {
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider->update([
            'reservation_note' => true,
            'reservation_note_text' => 'global note text',
        ]);

        $product = $this->makeTestProducts($provider)[0];

        // Create organization custom fields
        $organizationCustomFields = [[
            'label' => 'organization custom field text',
            'type' => ReservationField::TYPE_TEXT,
            'description' => 'organization custom field text description',
            'required' => true,
            'value' => 'some text',
            'fillable_by' => ReservationField::FILLABLE_BY_REQUESTER,
        ]];

        foreach ($organizationCustomFields as $order => $item) {
            $provider->reservation_fields()->create([
                ...Arr::only($item, ['label', 'type', 'description', 'required']),
                'order' => $order,
            ]);
        }

        $productCustomFields = [[
            'label' => 'product custom field text',
            'type' => ReservationField::TYPE_TEXT,
            'description' => 'product custom field text description',
            'required' => true,
            'value' => 'some text',
            'fillable_by' => ReservationField::FILLABLE_BY_REQUESTER,
        ]];

        // Assert if reservation_fields_config is 'yes' - product fields used
        $product = $this->updateProduct($provider, $product, fields: [
            'reservation_fields_enabled' => true,
            'reservation_fields_config' => $product::CUSTOM_RESERVATION_FIELDS_YES,
            'fields' => $productCustomFields,
        ]);

        $this->assertFieldsAvailableOnWebshop($provider, $product, $productCustomFields);

        // Assert if reservation_fields_config is 'global' - organization fields used
        $product = $this->updateProduct($provider, $product, fields: [
            'reservation_fields_config' => $product::CUSTOM_RESERVATION_FIELDS_GLOBAL,
        ]);

        $this->assertFieldsAvailableOnWebshop($provider, $product, $organizationCustomFields);

        // Assert if reservation_fields_config is 'no' - no fields used
        $product = $this->updateProduct($provider, $product, fields: [
            'reservation_fields_config' => $product::CUSTOM_RESERVATION_FIELDS_NO,
        ]);

        $this->assertFieldsAvailableOnWebshop($provider, $product, []);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testEditingProductReservationFieldsPreservesIdsAndOrder(): void
    {
        ['provider' => $provider, 'product' => $product, 'reservation' => $reservation] = $this->makeReservationContext();
        [$first, $second] = $this->makeReservationFields($provider, $product);
        $otherProduct = $this->makeTestProducts($provider)[0];
        [$unrelated] = $this->makeReservationFields($provider, $otherProduct);
        $unrelatedBefore = $unrelated->refresh()->getAttributes();
        $answer = $reservation->custom_fields()->create(['reservation_field_id' => $first->id, 'value' => 'Saved answer']);

        $this->updateProduct($provider, $product, [
            'reservation_fields_config' => Product::CUSTOM_RESERVATION_FIELDS_YES,
            'fields' => [
                $this->reservationFieldData('Second edited', $second->id),
                $this->reservationFieldData('First edited', $first->id),
                $this->reservationFieldData('Added'),
            ],
        ]);

        $new = $product->reservation_fields()->where('label', 'Added')->sole();

        $this->assertNotContains($new->id, [$first->id, $second->id]);

        $this->assertReservationFieldOrder($product, [$second->id, $first->id, $new->id], [
            'Second edited', 'First edited', 'Added',
        ]);

        $this->assertSame($first->id, $answer->refresh()->reservation_field_id);
        $this->assertSame('Saved answer', $answer->value);
        $this->assertSame('First edited', $answer->reservation_field->label);
        $this->assertSame($unrelatedBefore, $unrelated->refresh()->getAttributes());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOmittingProductReservationFieldArchivesOnlyThatField(): void
    {
        ['provider' => $provider, 'product' => $product, 'reservation' => $reservation] = $this->makeReservationContext();
        [$first, $second] = $this->makeReservationFields($provider, $product);
        $otherProduct = $this->makeTestProducts($provider)[0];
        [$unrelated] = $this->makeReservationFields($provider, $otherProduct);
        $unrelatedBefore = $unrelated->refresh()->getAttributes();

        $answer = $reservation->custom_fields()->create([
            'reservation_field_id' => $second->id,
            'value' => 'Saved answer',
        ]);

        $this->updateProduct($provider, $product, [
            'reservation_fields_config' => Product::CUSTOM_RESERVATION_FIELDS_YES,
            'fields' => [$this->reservationFieldData('First', $first->id)],
        ]);

        $this->assertReservationFieldOrder($product, [$first->id], ['First']);
        $this->assertSoftDeleted($second);
        $this->assertSame($second->id, $answer->refresh()->reservation_field_id);
        $this->assertSame('Saved answer', $answer->value);
        $this->assertTrue($answer->reservation_field->trashed());
        $this->assertSame($unrelatedBefore, $unrelated->refresh()->getAttributes());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testEditingOrganizationReservationFieldsPreservesIdsAndOrder(): void
    {
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $other = $this->makeTestProviderOrganization($this->makeIdentity());
        [$first, $second] = $this->makeReservationFields($provider);
        [$unrelated] = $this->makeReservationFields($other);
        $unrelatedBefore = $unrelated->refresh()->getAttributes();

        $this->apiUpdateOrganizationReservationFieldsRequest($provider, ['fields' => [
            $this->reservationFieldData('Second edited', $second->id),
            $this->reservationFieldData('First edited', $first->id),
            $this->reservationFieldData('Added'),
        ]], $provider->identity)->assertOk();

        $new = $provider->reservation_fields()->where('label', 'Added')->sole();

        $this->assertNotContains($new->id, [
            $first->id, $second->id,
        ]);

        $this->assertReservationFieldOrder($provider, [
            $second->id, $first->id, $new->id,
        ], [
            'Second edited', 'First edited', 'Added',
        ]);

        $this->assertSame($unrelatedBefore, $unrelated->refresh()->getAttributes());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testOmittingOrganizationReservationFieldArchivesOnlyThatField(): void
    {
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $other = $this->makeTestProviderOrganization($this->makeIdentity());
        [$first, $second] = $this->makeReservationFields($provider);
        [$unrelated] = $this->makeReservationFields($other);
        $unrelatedBefore = $unrelated->refresh()->getAttributes();

        $this->apiUpdateOrganizationReservationFieldsRequest($provider, ['fields' => [
            $this->reservationFieldData('First', $first->id),
        ]], $provider->identity)->assertOk();

        $this->assertReservationFieldOrder($provider, [$first->id], ['First']);
        $this->assertSoftDeleted($second);
        $this->assertSame($unrelatedBefore, $unrelated->refresh()->getAttributes());
    }

    /**
     * @param Organization $organization
     * @param Product $product
     * @param array $fields
     * @return Product
     */
    protected function updateProduct(
        Organization $organization,
        Product $product,
        array $fields,
    ): Product {
        $attributes = [
            ...$product->only([
                'name',
                'description',
                'price',
                'price_type',
                'product_category_id',
                'total_amount',
            ]),
            ...$fields,
        ];

        $response = $this->makeProductUpdateRequest($organization, $product, $attributes);
        $response->assertSuccessful();

        $data = $response->json('data.reservation_fields');

        if (Arr::has($fields, 'fields')) {
            foreach ($fields['fields'] as $index => $field) {
                $this->assertEquals($field['label'], $data[$index]['label']);
                $this->assertEquals($field['description'], $data[$index]['description']);
                $this->assertEquals($field['type'], $data[$index]['type']);
            }
        }

        return $product->refresh();
    }

    /**
     * @param Organization $organization
     * @param Product $product
     * @param array $fields
     * @return void
     */
    protected function assertFieldsAvailableOnWebshop(
        Organization $organization,
        Product $product,
        array $fields,
    ): void {
        $response = $this->apiGetProductRequest($product, $organization->identity, ['Client-Type' => 'webshop']);
        $response->assertSuccessful();
        $data = $response->json('data.reservation.fields');

        foreach ($fields as $index => $field) {
            $this->assertEquals($field['label'], $data[$index]['label']);
            $this->assertEquals($field['description'], $data[$index]['description']);
            $this->assertEquals($field['type'], $data[$index]['type']);
        }
    }

    /**
     * @param Organization $organization
     * @param Product|null $product
     * @return array{ReservationField, ReservationField}
     */
    private function makeReservationFields(Organization $organization, ?Product $product = null): array
    {
        $fields = [];

        foreach (['First', 'Second'] as $order => $label) {
            $fields[] = ReservationField::create([
                ...$this->reservationFieldData($label),
                'organization_id' => $organization->id,
                'product_id' => $product?->id,
                'order' => $order,
            ]);
        }

        return $fields;
    }

    /**
     * @param string $label
     * @param int|null $id
     * @return array
     */
    private function reservationFieldData(string $label, ?int $id = null): array
    {
        return [
            ...$id === null ? [] : ['id' => $id],
            'label' => $label,
            'type' => ReservationField::TYPE_TEXT,
            'description' => 'Description for ' . $label,
            'required' => false,
            'fillable_by' => ReservationField::FILLABLE_BY_REQUESTER,
        ];
    }

    /**
     * @param Organization|Product $owner
     * @param int[] $ids
     * @param string[] $labels
     * @return void
     */
    private function assertReservationFieldOrder(Organization|Product $owner, array $ids, array $labels): void
    {
        $fields = $owner->reservation_fields()->get();

        $this->assertSame($ids, $fields->modelKeys());
        $this->assertSame($labels, $fields->pluck('label')->all());
        $this->assertEquals(range(0, count($ids) - 1), $fields->pluck('order')->all());

        foreach ($fields as $field) {
            $this->assertSame('Description for ' . $field->label, $field->description);
            $this->assertFalse($field->trashed());
        }
    }
}
