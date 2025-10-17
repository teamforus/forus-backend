<?php

namespace Feature;

use App\Models\Organization;
use App\Models\OrganizationReservationField;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestProducts;
use Throwable;

class ProductReservationFieldsConfigTest extends TestCase
{
    use MakesTestProducts;
    use DatabaseTransactions;
    use MakesTestOrganizations;

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
            'type' => OrganizationReservationField::TYPE_TEXT,
            'description' => 'organization custom field text description',
            'required' => true,
            'value' => 'some text',
        ]];

        foreach ($organizationCustomFields as $order => $item) {
            $provider->reservation_fields()->create([
                ...Arr::only($item, ['label', 'type', 'description', 'required']),
                'order' => $order,
            ]);
        }

        $productCustomFields = [[
            'label' => 'product custom field text',
            'type' => OrganizationReservationField::TYPE_TEXT,
            'description' => 'product custom field text description',
            'required' => true,
            'value' => 'some text',
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
     * @param Organization $organization
     * @param Product $product
     * @param array $params
     * @return TestResponse
     */
    protected function makeProductUpdateRequest(
        Organization $organization,
        Product $product,
        array $params
    ): TestResponse {
        return $this->patchJson(
            "/api/v1/platform/organizations/$organization->id/products/$product->id",
            $params,
            $this->makeApiHeaders($organization->identity),
        );
    }

    /**
     * @param Organization $organization
     * @param Product $product
     * @return TestResponse
     */
    protected function makeWebshopProductGetRequest(
        Organization $organization,
        Product $product,
    ): TestResponse {
        return $this->getJson("/api/v1/platform/products/$product->id", $this->makeApiHeaders($organization->identity, [
            'Client-Type' => 'webshop',
        ]));
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
        $response = $this->makeWebshopProductGetRequest($organization, $product);
        $response->assertSuccessful();
        $data = $response->json('data.reservation.fields');

        foreach ($fields as $index => $field) {
            $this->assertEquals($field['label'], $data[$index]['label']);
            $this->assertEquals($field['description'], $data[$index]['description']);
            $this->assertEquals($field['type'], $data[$index]['type']);
        }
    }
}
