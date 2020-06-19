<?php

namespace App\Digests;

use App\Mail\Digest\BaseDigestMail;
use App\Mail\Digest\DigestProviderProductsMail;
use App\Mail\MailBodyBuilder;
use App\Models\Product;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\EventLogService\Models\EventLog;
use App\Services\Forus\Notification\NotificationService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

/**
 * Class ValidatorDigest
 * @package App\Digests
 */
class ProviderProductsDigest extends BaseOrganizationDigest
{
    use Dispatchable;

    protected $requiredRelation = 'fund_providers';
    protected $digestKey = 'provider_products';
    protected $employeePermissions = [
        'manage_provider_funds'
    ];

    /**
     * @param Organization $organization
     * @param NotificationService $notificationService
     */
    public function handleOrganizationDigest(
        Organization $organization,
        NotificationService $notificationService
    ): void {
        $emailBodyProducts = new MailBodyBuilder();

        $events = $this->getOrganizationProductReservedEvents($organization);
        $logsProductsReserved = $events->groupBy('fund_id');
        $totalProducts = $events->groupBy('product_id')->count();

        if ($totalProducts === 0) {
            $this->updateLastDigest($organization);
            return;
        }

        foreach ($logsProductsReserved as $logsProductReserved) {
            $emailBodyProducts->h3(trans('digests/provider_products.fund_title', [
                'fund_name' => $logsProductReserved[0]['fund_name'],
            ]));

            $logsProductReserved = $logsProductReserved->groupBy('product_id');

            foreach ($logsProductReserved as $_logsProductReserved) {
                $count_reservations = $_logsProductReserved->count();
                $emailBodyProducts->text(trans_choice(
                    'digests/provider_products.fund_products',
                    $count_reservations,
                    array_merge($_logsProductReserved[0], compact('count_reservations'))
                ));
            }
        }

        $emailBody = new MailBodyBuilder();
        $emailBody->h1(trans_choice("digests/provider_products.title", $totalProducts, [
            'provider_name' => $organization->name,
            'count_products' => $totalProducts,
        ]));

        $emailBody->text(trans_choice("digests/provider_products.greetings", $totalProducts, [
            'provider_name' => $organization->name,
            'count_products' => $totalProducts,
        ]));

        $emailBody = $emailBody->merge($emailBodyProducts)->space()->button_primary(
            Implementation::general_urls()['url_provider'],
            trans('digests/provider_products.dashboard_button')
        );

        $this->sendOrganizationDigest($organization, $emailBody, $notificationService);
    }

    /**
     * @param Organization $organization
     * @return Collection
     */
    public function getOrganizationProductReservedEvents(Organization $organization): Collection {
        $query = EventLog::eventsOfTypeQuery(
            Product::class,
            $organization->products()->pluck('id')->toArray()
        )->where('event', Product::EVENT_RESERVED);
        $query->where('created_at', '>=', $this->getOrganizationDigestTime($organization));

        return $query->get()->pluck('data');
    }

    /**
     * @param MailBodyBuilder $emailBody
     * @return BaseDigestMail
     */
    protected function getDigestMailable(MailBodyBuilder $emailBody): BaseDigestMail
    {
        return new DigestProviderProductsMail(compact('emailBody'));
    }
}