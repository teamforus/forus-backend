<?php

namespace App\Listeners;

use App\Events\Funds\FundProductAddedEvent;
use App\Events\Funds\FundProductApprovedEvent;
use App\Events\Funds\FundProductRevokedEvent;
use App\Events\Products\ProductApproved;
use App\Events\Products\ProductCreated;
use App\Events\Products\ProductExpired;
use App\Events\Products\ProductReserved;
use App\Events\Products\ProductRevoked;
use App\Events\Products\ProductSoldOut;
use App\Events\Products\ProductUpdated;
use App\Models\FundProviderChat;
use App\Notifications\Organizations\Products\ProductApprovedNotification;
use App\Notifications\Organizations\Products\ProductExpiredNotification;
use App\Notifications\Organizations\Products\ProductReservedNotification;
use App\Notifications\Organizations\Products\ProductRevokedNotification;
use App\Notifications\Organizations\Products\ProductSoldOutNotification;
use Illuminate\Events\Dispatcher;

/**
 * Class ProductSubscriber
 * @package App\Listeners
 */
class ProductSubscriber
{
    /**
     * @param ProductCreated $productCreated
     */
    public function onProductCreated(ProductCreated $productCreated): void
    {
        $product = $productCreated->getProduct();

        $product->update([
            'description_text' => $product->descriptionToText(),
        ]);

        $product->log($product::EVENT_CREATED, [
            'product' => $product,
            'provider' => $product->organization,
        ]);

        foreach ($product->organization->supplied_funds_approved_products as $fund) {
            FundProductAddedEvent::dispatch($fund, $product);
        }
    }

    /**
     * @param ProductUpdated $productUpdated
     */
    public function onProductUpdated(ProductUpdated $productUpdated): void
    {
        /** @var FundProviderChat[] $chats */
        $product = $productUpdated->getProduct();
        $chats = $product->fund_provider_chats()->get();

        $product->update([
            'description_text' => $product->descriptionToText(),
        ]);

        foreach ($chats as $chat) {
            $chat->addSystemMessage('Aanbieding aangepast.', auth_address());
        }

        $product->updateSoldOutState();
    }

    /**
     * @param ProductSoldOut $productSoldOut
     */
    public function onProductSoldOut(ProductSoldOut $productSoldOut): void
    {
        $product = $productSoldOut->getProduct();

        ProductSoldOutNotification::send($product->log($product::EVENT_SOLD_OUT, [
            'product' => $product,
            'provider' => $product->organization,
        ]));
    }

    /**
     * @param ProductExpired $productExpired
     */
    public function onProductExpired(ProductExpired $productExpired): void
    {
        $product = $productExpired->getProduct();

        ProductExpiredNotification::send($product->log($product::EVENT_EXPIRED, [
            'product' => $product,
            'provider' => $product->organization,
        ]));
    }

    /**
     * @param ProductReserved $productReserved
     */
    public function onProductReserved(ProductReserved $productReserved): void
    {
        $product = $productReserved->getProduct();
        $voucher = $productReserved->getVoucher();

        ProductReservedNotification::send($product->log($product::EVENT_RESERVED, [
            'fund' => $voucher->fund,
            'sponsor' => $voucher->fund->organization,
            'product' => $product,
            'provider' => $product->organization,
            'implementation' => $voucher->fund->getImplementation(),
        ], [
            'expiration_date' => format_date_locale($voucher->last_active_day),
        ]));
    }

    /**
     * @param ProductApproved $productApproved
     */
    public function onProductApproved(ProductApproved $productApproved): void
    {
        $fund = $productApproved->getFund();
        $product = $productApproved->getProduct();

        foreach ($product->fund_provider_chats as $chat) {
            $chat->addSystemMessage('Aanbieding geaccepteerd.', auth_address());
        }

        FundProductApprovedEvent::dispatch($fund, $product);

        ProductApprovedNotification::send($product->log($product::EVENT_APPROVED, [
            'fund' => $fund,
            'product' => $product,
            'sponsor' => $fund->organization,
            'provider' => $product->organization,
            'implementation' => $fund->getImplementation(),
        ]));
    }

    /**
     * @param ProductRevoked $productRevoked
     */
    public function onProductRevoked(ProductRevoked $productRevoked): void
    {
        $fund = $productRevoked->getFund();
        $product = $productRevoked->getProduct();

        FundProductRevokedEvent::dispatch($fund, $product);

        ProductRevokedNotification::send($product->log($product::EVENT_REVOKED, [
            'fund' => $fund,
            'product' => $product,
            'sponsor' => $fund->organization,
            'provider' => $product->organization,
            'implementation' => $fund->getImplementation(),
        ]));
    }

    /**
     * The events dispatcher
     *
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events): void
    {
        $events->listen(ProductRevoked::class, '\App\Listeners\ProductSubscriber@onProductRevoked');
        $events->listen(ProductCreated::class, '\App\Listeners\ProductSubscriber@onProductCreated');
        $events->listen(ProductUpdated::class, '\App\Listeners\ProductSubscriber@onProductUpdated');
        $events->listen(ProductSoldOut::class, '\App\Listeners\ProductSubscriber@onProductSoldOut');
        $events->listen(ProductExpired::class, '\App\Listeners\ProductSubscriber@onProductExpired');
        $events->listen(ProductReserved::class, '\App\Listeners\ProductSubscriber@onProductReserved');
        $events->listen(ProductApproved::class, '\App\Listeners\ProductSubscriber@onProductApproved');
    }
}
