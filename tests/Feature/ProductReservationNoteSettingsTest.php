<?php

namespace Feature;

use App\Models\Organization;
use App\Models\Product;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\MakesTestOrganizations;
use Tests\Traits\MakesTestProducts;
use Throwable;

class ProductReservationNoteSettingsTest extends TestCase
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
    public function testUpdateProductReservationNoteSettingsSuccess(): void
    {
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $provider->update([
            'reservation_note' => true,
            'reservation_note_text' => 'global note text',
        ]);

        $product = $this->makeTestProducts($provider)[0];

        $this->assertProductReservationNoteSettingsSuccess($provider, $product, fields: [
            'reservation_note' => 'no',
        ]);

        $this->assertProductReservationNoteSettingsSuccess($provider, $product, [
            'reservation_note' => 'global',
        ], assertNote: $provider->reservation_note_text);

        $this->assertProductReservationNoteSettingsSuccess($provider, $product, [
            'reservation_note' => 'global',
            'reservation_note_text' => 'custom note text',
        ], assertNote: $provider->reservation_note_text);

        $this->assertProductReservationNoteSettingsSuccess($provider, $product, [
            'reservation_note' => 'custom',
            'reservation_note_text' => 'custom note text',
        ], assertNote: 'custom note text');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testUpdateOrganizationReservationNoteSettingsFail(): void
    {
        $provider = $this->makeTestProviderOrganization($this->makeIdentity());
        $product = $this->makeTestProducts($provider)[0];

        $this->makeProductUpdateRequest($provider, $product, [
            'reservation_note' => 'invalid value',
            'reservation_note_text' => ['test note text'],
        ])->assertJsonValidationErrors(['reservation_note', 'reservation_note_text']);

        $this->makeProductUpdateRequest($provider, $product, ['reservation_note' => 'custom'])
            ->assertJsonValidationErrors(['reservation_note_text']);

        $this->makeProductUpdateRequest($provider, $product, ['reservation_note_text' => true])
            ->assertJsonValidationErrors(['reservation_note_text']);
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
     * @param array $fields
     * @param string|null $assertNote
     * @return void
     */
    protected function assertProductReservationNoteSettingsSuccess(
        Organization $organization,
        Product $product,
        array $fields,
        ?string $assertNote = null,
    ): void {
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

        $product->refresh();
        $data = $response->json('data');

        foreach ($fields as $key => $value) {
            $this->assertEquals($value, $data[$key]);
            $this->assertEquals($value, $product->$key);
        }

        $this->assertEquals($assertNote, $product->getReservationNoteValue());
    }
}
