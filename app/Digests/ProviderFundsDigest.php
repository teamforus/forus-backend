<?php


namespace App\Digests;

use App\Mail\MailBodyBuilder;
use App\Models\Employee;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\Product;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\EventLogService\Models\EventLog;
use App\Services\Forus\Identity\Models\Identity;
use App\Services\Forus\Notification\NotificationService;
use Illuminate\Foundation\Bus\Dispatchable;

/**
 * Class ValidatorDigest
 * @package App\Digests
 */
class ProviderFundsDigest
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
        $mailBody = new MailBodyBuilder();
        $mailBody->h1("Update: huidige status van uw aanmelding met {$organization->name}");

        $mailBody = $mailBody->merge(
            $this->getOrganizationBudgetApprovalMailBody($organization),
            $this->getOrganizationProductsApprovalMailBody($organization),
            $this->getOrganizationBudgetRevokedMailBody($organization),
            $this->getOrganizationProductsRevokedMailBody($organization),
            $this->getOrganizationIndividualProductsMailBody($organization),
            $this->getOrganizationProductSponsorFeedbackMailBody($organization)
        );

        if ($mailBody->count() === 1) {
            $this->updateLastDigest($organization);
            return;
        }

        $mailBody->space()->button_primary(
            Implementation::general_urls()['url_provider'],
            'GA NAAR HET DASHBOARD'
        );
        $this->sendOrganizationDigest($organization, $mailBody, $notificationService);
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
                $notificationService->dailyDigestProviderFunds($identity->email, [
                    'emailBody' => $emailBody
                ]);
            }
        }

        $this->updateLastDigest($organization);
    }

    /**
     * @param Organization $organization
     * @return MailBodyBuilder
     */
    private function getOrganizationBudgetApprovalMailBody(
        Organization $organization
    ): MailBodyBuilder {
        $mailBody = new MailBodyBuilder();

        $query = EventLog::eventsOfTypeQuery(
            FundProvider::class,
            $organization->fund_providers()->pluck('id')->toArray()
        )->where('event', FundProvider::EVENT_APPROVED_BUDGET);
        $query->where('created_at', '>=', $this->getOrganizationDigestTime($organization));

        $logsApprovedBudget = $query->get()->pluck('data');

        if ($logsApprovedBudget->count() > 0) {
            $mailBody->h3(sprintf(
                "Your application have been approved for %s fund(s) to scan vouchers",
                $logsApprovedBudget->count()
            ));

            $mailBody->text(sprintf(
                "This means you can scan budget vouchers for people who bla bla.\nYou have been approved for:\n- %s",
                implode("\n- ", $logsApprovedBudget->pluck('fund_name')->toArray())
            ));

            $mailBody->text("There specific rights for each fund assigned to your organization.\nPlease check the dashboard to see full context.");
            $mailBody->separator();
        }

        return $mailBody;
    }

    /**
     * @param Organization $organization
     * @return MailBodyBuilder
     */
    private function getOrganizationProductsApprovalMailBody(
        Organization $organization
    ): MailBodyBuilder {
        $mailBody = new MailBodyBuilder();

        $query = EventLog::eventsOfTypeQuery(
            FundProvider::class,
            $organization->fund_providers()->pluck('id')->toArray()
        )->where('event', FundProvider::EVENT_APPROVED_PRODUCTS);
        $query->where('created_at', '>=', $this->getOrganizationDigestTime($organization));

        $logsApprovedProducts = $query->get()->pluck('data');

        if ($logsApprovedProducts->count() > 0) {
            $mailBody->h3(sprintf(
                "Your application have been approved for %s fund(s) to sell product vouchers",
                $logsApprovedProducts->count()
            ));

            $mailBody->text(sprintf(
                "This means you can have your products on their webshop and sell directly online.\nYou have been approved for:\n- %s",
                implode("\n- ", $logsApprovedProducts->pluck('fund_name')->toArray())
            ))->separator();
        }

        return $mailBody;
    }

    /**
     * @param Organization $organization
     * @return MailBodyBuilder
     */
    private function getOrganizationBudgetRevokedMailBody(
        Organization $organization
    ): MailBodyBuilder {
        $mailBody = new MailBodyBuilder();

        $query = EventLog::eventsOfTypeQuery(
            FundProvider::class,
            $organization->fund_providers()->pluck('id')->toArray()
        )->where('event', FundProvider::EVENT_REVOKED_BUDGET);
        $query->where('created_at', '>=', $this->getOrganizationDigestTime($organization));

        $logsRejectedBudget = $query->get()->pluck('data');

        if ($logsRejectedBudget->count() > 0) {
            $mailBody->h3(sprintf(
                "Your application has been rejected for %s fund(s) to scan vouchers",
                $logsRejectedBudget->count()
            ));

            $mailBody->text(sprintf(
                "This means your application for these funds have been changed:\n - %s",
                implode("\n- ", $logsRejectedBudget->pluck('fund_name')->toArray())
            ));

            $mailBody->text("Please check your dashboard for the current status.");
            $mailBody->text("There specific rights for each fund assigned to your organization.\nPlease check the dashboard to see full context.");
            $mailBody->separator();
        }

        return $mailBody;
    }

    /**
     * @param Organization $organization
     * @return MailBodyBuilder
     */
    private function getOrganizationProductsRevokedMailBody(
        Organization $organization
    ): MailBodyBuilder {
        $mailBody = new MailBodyBuilder();

        $query = EventLog::eventsOfTypeQuery(
            FundProvider::class,
            $organization->fund_providers()->pluck('id')->toArray()
        )->where('event', FundProvider::EVENT_REVOKED_PRODUCTS);
        $query->where('created_at', '>=', $this->getOrganizationDigestTime($organization));

        $logsRejectedProducts = $query->get()->pluck('data');

        if ($logsRejectedProducts->count() > 0) {
            $mailBody->h3(sprintf(
                "Your application has been rejected for %s fund(s) to scan product vouchers",
                $logsRejectedProducts->count()
            ));

            $mailBody->text(sprintf(
                "This means can not scan product vouchers for these funds anymore:\n- %s",
                implode("\n- ", $logsRejectedProducts->pluck('fund_name')->toArray())
            ));

            $fundsWithSomeProducts = $organization->fund_providers()->whereKey(
                $logsRejectedProducts->pluck('fund_id')->unique()->toArray()
            )->whereHas('fund_provider_products')->pluck('fund_id')->unique();

            if ($fundsWithSomeProducts->count() > 0) {
                $mailBody->text(sprintf(
                    "For these funds you still have some products approved:\n- %s",
                    implode("\n- ", Fund::whereKey($fundsWithSomeProducts)->pluck('name')->toArray())
                ));
            }

            $mailBody->text("Please check your dashboard for more details.");
            $mailBody->separator();
        }

        return $mailBody;
    }

    /**
     * @param Organization $organization
     * @return MailBodyBuilder
     */
    private function getOrganizationIndividualProductsMailBody(
        Organization $organization
    ): MailBodyBuilder {
        $mailBody = new MailBodyBuilder();

        $query = EventLog::eventsOfTypeQuery(
            Product::class,
            $organization->products()->pluck('id')->toArray()
        )->where('event', Product::EVENT_APPROVED);
        $query->where('created_at', '>=', $this->getOrganizationDigestTime($organization));

        $logsProductsApproved = $query->get()->pluck('data');

        if ($logsProductsApproved->count() > 0) {
            $mailBody->h3("Some of your products have been individually accepted for these funds.");
            $mailBody->text("There specific rights for each fund assigned to your organization. \nPlease check the dashboard to see full context\n");

            foreach ($logsProductsApproved->groupBy('fund_id') as $logsProductApproved) {
                $mailBody->h5($logsProductApproved[0]['fund_name'], ['margin_less']);
                $mailBody->text(implode("\n", array_map(static function($log) {
                        return sprintf("- %s voor €%s", $log['product_name'], $log['product_price_locale']);
                    }, $logsProductApproved->toArray())) . "\n");
            }

            $mailBody->separator();
        }

        return $mailBody;
    }

    private function getOrganizationProductSponsorFeedbackMailBody(
        Organization $organization
    ): MailBodyBuilder {
        $mailBody = new MailBodyBuilder();

        $query = EventLog::eventsOfTypeQuery(
            FundProvider::class,
            $organization->fund_providers()->pluck('id')->toArray()
        )->where('event', FundProvider::EVENT_SPONSOR_MESSAGE);
        $query->where('created_at', '>=', $this->getOrganizationDigestTime($organization));

        $logsProductsFeedback = $query->get()->pluck('data');
        $logsProductsFeedback = $logsProductsFeedback->groupBy('product_id');

        if ($logsProductsFeedback->count() > 0) {
            $mailBody->h3(sprintf(
                "Feedback on %s of your product(s)",
                $logsProductsFeedback->count()
            ));

            $mailBody->text(sprintf(
                "You got feedback on %s of your products",
                $logsProductsFeedback->count()
            ));

            foreach ($logsProductsFeedback as $logsProductFeedback) {
                $mailBody->h5(sprintf(
                    "New messages on %s for €%s",
                    $logsProductFeedback[0]['product_name'],
                    $logsProductFeedback[0]['product_price_locale']
                ), ['margin_less']);

                foreach ($logsProductFeedback->groupBy('fund_id') as $logsProductFeedbackLog) {
                    $mailBody->text(sprintf(
                        "- %s - has sent %s message(s) on your application for %s.\n",
                        $logsProductFeedbackLog[0]['sponsor_name'],
                        $logsProductFeedbackLog->count(),
                        $logsProductFeedbackLog[0]['fund_name']
                    ));
                }
            }

            $mailBody->separator();
        }

        return $mailBody;
    }

    /**
     * @param Organization $organization
     * @return \Carbon\Carbon|\Illuminate\Support\Carbon
     */
    public function getOrganizationDigestTime(
        Organization $organization
    ) {
        return $organization->lastDigestOfType('provider_funds')->created_at ?? now()->subDay();
    }

    protected function updateLastDigest(Organization $organization): void {
        $organization->digests()->create([
            'type' => 'provider_funds'
        ]);
    }
}