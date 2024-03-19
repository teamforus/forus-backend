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

}
