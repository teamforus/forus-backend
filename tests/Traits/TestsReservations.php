<?php

namespace Tests\Traits;

use App\Models\ProductReservation;
use Illuminate\Testing\TestResponse;
use App\Models\Product;
use App\Models\Voucher;
use Tests\TestCase;

/**
 * @mixin TestCase
 */
trait TestsReservations
{
    /**
     * @param Voucher $voucher
     * @param Product $product
     * @param array $fields
     * @param bool $auth
     * @return TestResponse
     */
    public function makeReservationStoreRequest(
        Voucher $voucher,
        Product $product,
        array $fields = [],
        bool $auth = true,
    ): TestResponse {
        return $this->postJson('/api/v1/platform/product-reservations', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'user_note' => '',
            'voucher_id' => $voucher->id,
            'product_id' => $product->id,
            ...$fields,
        ], $auth ? $this->makeApiHeaders($this->makeIdentityProxy($voucher->identity)) : []);
    }

    /**
     * @param ProductReservation $reservation
     * @return TestResponse
     */
    public function makeReservationCancelRequest(ProductReservation $reservation): TestResponse
    {
        return $this->postJson(
            "/api/v1/platform/product-reservations/$reservation->id/cancel",
            [],
            $this->makeApiHeaders($this->makeIdentityProxy($reservation->voucher->identity)),
        );
    }


    /**
     * @param ProductReservation $reservation
     * @return TestResponse
     */
    public function makeReservationGetRequest(ProductReservation $reservation): TestResponse
    {
        return $this->getJson(
            "/api/v1/platform/product-reservations/$reservation->id",
            $this->makeApiHeaders($this->makeIdentityProxy($reservation->voucher->identity)),
        );
    }

    /**
     * @param ProductReservation $reservation
     * @return TestResponse
     */
    public function makeReservationAcceptRequest(ProductReservation $reservation): TestResponse
    {
        $organization = $reservation->product->organization;

        return $this->postJson(
            "/api/v1/platform/organizations/$organization->id/product-reservations/$reservation->id/accept",
            [],
            $this->makeApiHeaders($this->makeIdentityProxy($organization->identity)),
        );
    }
}