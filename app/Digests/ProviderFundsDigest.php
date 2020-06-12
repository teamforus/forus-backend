<?php


namespace App\Digests;

use App\Mail\Digest\BaseDigestMail;
use App\Mail\Digest\DigestProviderFundsMail;
use App\Mail\MailBodyBuilder;
use App\Models\Fund;
use App\Models\FundProvider;
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
class ProviderFundsDigest extends BaseOrganizationDigest
{
    use Dispatchable;

    protected $requiredRelation = 'fund_providers';
    protected $digestKey = 'provider_funds';
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
     * @param string $targetEvent
     * @param array $otherEvents
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Collection
     */
    protected function getEvents(
        Organization $organization,
        string $targetEvent,
        array $otherEvents
    ) {
        $logsApprovedBudget = EventLog::eventsOfTypeQuery(
            FundProvider::class,
            $organization->fund_providers()->pluck('id')->toArray()
        )->whereIn('event', $otherEvents)->where(
            'created_at', '>=', $this->getOrganizationDigestTime($organization)
        )->get()->groupBy('loggable_id');

        return $logsApprovedBudget->filter(static function(
            Collection $group
        ) use ($targetEvent) {
            $group = $group->sortBy('created_at');
            return ($group->first()->event === $group->last()->event) &&
                $group->last()->event === $targetEvent;
        })->map(static function(Collection $group) {
            return $group->sortBy('created_at')->last();
        })->flatten(1)->pluck('data');
    }

    /**
     * @param Organization $organization
     * @return MailBodyBuilder
     */
    private function getOrganizationBudgetApprovalMailBody(
        Organization $organization
    ): MailBodyBuilder {
        $mailBody = new MailBodyBuilder();
        $logsApprovedBudget = $this->getEvents(
            $organization,
            FundProvider::EVENT_APPROVED_BUDGET, [
            FundProvider::EVENT_APPROVED_BUDGET,
            FundProvider::EVENT_REVOKED_BUDGET
        ]);

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
        $logsApprovedProducts = $this->getEvents(
            $organization,
            FundProvider::EVENT_APPROVED_PRODUCTS, [
            FundProvider::EVENT_APPROVED_PRODUCTS,
            FundProvider::EVENT_REVOKED_PRODUCTS
        ]);

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
        $logsRejectedBudget = $this->getEvents(
            $organization,
            FundProvider::EVENT_REVOKED_BUDGET, [
            FundProvider::EVENT_APPROVED_BUDGET,
            FundProvider::EVENT_REVOKED_BUDGET
        ]);

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
        $logsRejectedProducts = $this->getEvents(
            $organization,
            FundProvider::EVENT_REVOKED_PRODUCTS, [
            FundProvider::EVENT_APPROVED_PRODUCTS,
            FundProvider::EVENT_REVOKED_PRODUCTS
        ]);

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
     * @param MailBodyBuilder $emailBody
     * @return BaseDigestMail
     */
    protected function getDigestMailable(MailBodyBuilder $emailBody): BaseDigestMail
    {
        return new DigestProviderFundsMail(compact('emailBody'));
    }
}