<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesTestFundProviders;

class ProductExpiredTest extends TestCase
{
    use MakesTestFundProviders;
    use DatabaseTransactions;

    /**
     * @return void
     */
    public function testProductExpired(): void
    {
        $product = $this->makeTestProviderWithProducts(1)[0];
        $this->assertFalse($product->expired);

        $product->update(['expire_at' => now()]);
        $this->assertFalse($product->expired, 'Product should not be expired if expire_at is today');

        $this->travelTo(now()->addDay());
        $this->assertTrue($product->refresh()->expired);
    }
}
