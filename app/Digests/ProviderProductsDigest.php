<?php


namespace App\Digests;

use App\Mail\MailBodyBuilder;
use App\Models\Employee;
use App\Models\Product;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\EventLogService\Models\EventLog;
use App\Services\Forus\Identity\Models\Identity;
use App\Services\Forus\Notification\NotificationService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

/**
 * Class ValidatorDigest
 * @package App\Digests
 */
class ProviderProductsDigest
{
    use Dispatchable;

    /**
     * @param NotificationService $notificationService
     */
    public function handle(NotificationService $notificationService): void
    {
        /** @var Organization[] $organizations */
        $organizations = Organization::whereHas('fund_providers')->get();

        foreach ($organizations as $organization) {
            $this->handleOrganizationDigest($organization, $notificationService);
        }
    }

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
            $emailBodyProducts->h3(sprintf(
                "Your product has been reserved with %s",
                $logsProductReserved[0]['fund_name']
            ));

            $logsProductReserved = $logsProductReserved->groupBy('product_id');

            foreach ($logsProductReserved as $_logsProductReserved) {
                $emailBodyProducts->text(sprintf(
                    "- %s - %s reservation(s)\nThe last day the customer can pick up the reservation is %s",
                    $_logsProductReserved[0]['product_name'],
                    $_logsProductReserved->count(),
                    $_logsProductReserved[0]['fund_end_date_locale']
                ));
            }
        }

        $emailBody = new MailBodyBuilder();
        $emailBody->h1(sprintf(
            "Overview: %s new product(s) reserved %s",
            $totalProducts,
            $organization->name
        ));

        $emailBody->text(sprintf(
            "Beste %s,\nEr are %s products reserved today.",
            $organization->name,
            $totalProducts
        ));

        $emailBody = $emailBody->merge($emailBodyProducts)->space()->button_primary(
            Implementation::general_urls()['url_provider'],
            'GA NAAR HET DASHBOARD'
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
     * @param Organization $organization
     * @param MailBodyBuilder $emailBody
     * @param NotificationService $notificationService
     */
    protected function sendOrganizationDigest(
        Organization $organization,
        MailBodyBuilder $emailBody,
        NotificationService $notificationService
    ): void {
        /** @var Employee[] $employees */
        $employees = $organization->employeesWithPermissions('manage_provider_funds');

        foreach ($employees as $employee) {
            if ($identity = Identity::findByAddress($employee->identity_address)) {
                $notificationService->dailyDigestProviderProducts($identity->email, compact('emailBody'));
            }
        }

        $this->updateLastDigest($organization);
    }

    /**
     * @param Organization $organization
     * @return \Carbon\Carbon|\Illuminate\Support\Carbon
     */
    public function getOrganizationDigestTime(
        Organization $organization
    ) {
        return $organization->lastDigestOfType('provider_products')->created_at ?? now()->subDay();
    }

    /**
     * @param Organization $organization
     */
    protected function updateLastDigest(Organization $organization): void {
        $organization->digests()->create([
            'type' => 'provider_products'
        ]);
    }
}