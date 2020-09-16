<?php
/**
 * Created by PhpStorm.
 * User: aghimpu
 * Date: 9/15/20
 * Time: 11:18 PM
 */

namespace App\Notifications\Organizations\Products;

use App\Mail\Products\ProductActionsRemovedMail;
use App\Models\Fund;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\Forus\Identity\Models\Identity;


class ProductActionsRemovedNotification extends BaseProductsNotification
{
    protected $key = 'notifications_products.actions_removed';
    protected $sendMail = true;

    protected static $permissions = [
        'manage_products',
    ];

    /**
     * Get the mail representation of the notification.
     *
     * @param Identity $identity
     */
    public function toMail(Identity $identity): void {
        $fund = Fund::find($this->eventLog->data['fund_id']);
        $sponsor = Organization::find($this->eventLog->data['sponsor_id']);

//        logger()->info('eventLog: '. print_r($this->eventLog->data, true));
//        logger()->info('sponsor->email: '. print_r($sponsor->email, true));
        logger()->info('toMail: ');

        resolve('forus.services.notification')->sendMailNotification(
            $sponsor->email,
            new ProductActionsRemovedMail(
                $this->eventLog->data['product_name'],
                $this->eventLog->data['provider_name'],
                $this->eventLog->data['sponsor_name'],
                $fund->fund_config->implementation->url_provider ?? env('PANEL_SPONSOR_URL_URL'),
                Implementation::activeModel()->getEmailFrom()
            )
        );
    }
}