<?php

namespace App\Listeners;

use App\Events\Products\ProductCreated;
use App\Events\Products\ProductUpdated;
use App\Models\FundProviderChat;
use App\Models\Voucher;
use Illuminate\Events\Dispatcher;

class ProductSubscriber
{
    public function onProductCreated(ProductCreated $productCreated) {
        // $product = $productCreated->getProduct();
    }

    public function onProductUpdated(ProductUpdated $productUpdated) {
        /** @var FundProviderChat[] $chats */
        $product = $productUpdated->getProduct();
        $chats = $product->fund_provider_chats()->get();

        foreach ($chats as $chat) {
            $chat->addSystemMessage('Aanbieding aangepast.', auth_address());
        }

        $product->vouchers()->each(function (Voucher $voucher) {
            $voucher->update([
                'expire_at' => $voucher->fund->end_date->gt(
                    $voucher->product->expire_at
                ) ? $voucher->product->expire_at : $voucher->fund->end_date
            ]);
        });

        $product->updateSoldOutState();
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        $events->listen(
            ProductCreated::class,
            '\App\Listeners\ProductSubscriber@onProductCreated'
        );

        $events->listen(
            ProductUpdated::class,
            '\App\Listeners\ProductSubscriber@onProductUpdated'
        );
    }
}
