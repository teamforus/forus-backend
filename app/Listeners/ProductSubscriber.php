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
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundProviderChat;
use App\Notifications\Organizations\Products\ProductApprovedNotification;
use App\Notifications\Organizations\Products\ProductExpiredNotification;
use App\Notifications\Organizations\Products\ProductReservedNotification;
use App\Notifications\Organizations\Products\ProductRevokedNotification;
use App\Notifications\Organizations\Products\ProductSoldOutNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Events\Dispatcher;

class ProductSubscriber
{
    /**
     * @param ProductCreated $productCreated
     * @noinspection PhpUnused
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

        $funds = Fund::whereHas('fund_providers', function (Builder $builder) use ($product) {
            $builder->where('organization_id', $product->organization_id);
            $builder->where('state', FundProvider::STATE_ACCEPTED);
        })->get();

        foreach ($funds as $fund) {
            FundProductAddedEvent::dispatch($fund, $product);
        }
    }

    /**
     * @param ProductUpdated $productUpdated
     * @noinspection PhpUnused
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
            $chat->addSystemMessage('Aanbieding aangepast.', auth()->id());
        }

        $product->updateSoldOutState();
    }

    /**
     * @param ProductSoldOut $productSoldOut
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
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
     * @noinspection PhpUnused
     */
    public function onProductApproved(ProductApproved $productApproved): void
    {
        $fund = $productApproved->getFund();
        $product = $productApproved->getProduct();

        foreach ($product->fund_provider_chats as $chat) {
            $chat->addSystemMessage('Aanbieding geaccepteerd.', auth()->id());
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
     * @noinspection PhpUnused
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
     * @return void
     * @noinspection PhpUnused
     */
    public function subscribe(Dispatcher $events): void
    {
        $class = '\\' . static::class;

        $events->listen(ProductRevoked::class, "$class@onProductRevoked");
        $events->listen(ProductCreated::class, "$class@onProductCreated");
        $events->listen(ProductUpdated::class, "$class@onProductUpdated");
        $events->listen(ProductSoldOut::class, "$class@onProductSoldOut");
        $events->listen(ProductExpired::class, "$class@onProductExpired");
        $events->listen(ProductReserved::class, "$class@onProductReserved");
        $events->listen(ProductApproved::class, "$class@onProductApproved");
    }
}
