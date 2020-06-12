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
                "Uw aanmelding voor %s fonds(en) is goedgekeurd om tegoeden te scannen.",
                $logsApprovedBudget->count()
            ));

            $mailBody->text(sprintf(
                "Dit betekent dat u vanaf nu tegoeden kunt scannen en kunt afschrijven. \nU bent goedgekeurt voor:\n- %s",
                implode("\n- ", $logsApprovedBudget->pluck('fund_name')->toArray())
            ));

            $mailBody->text("Er zijn specifieke rechten aan u toegekend per fonds.\nBekijk het dashboard voor de volledige context.");
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
                "Uw aanmelding voor %s fondsen is goedgekeurd met al uw producten.",
                $logsApprovedProducts->count()
            ));

            $mailBody->text(sprintf(
                "Dit betekent dat uw producten in de webshop staan voor de volgende fondsen:\n- %s",
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
                "Uw aanmelding voor %s fonds(en) is geweigerd om tegoeden te scannen.",
                $logsRejectedBudget->count()
            ));

            $mailBody->text(sprintf(
                "Dit betekent dat uw aanmelding voor de volgende fondsen is gewijzigd:\n - %s",
                implode("\n- ", $logsRejectedBudget->pluck('fund_name')->toArray())
            ));

            $mailBody->text("Er zijn specifieke rechten aan u toegekend.\nBekijk het dashboard voor de huidige status.");
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
                "Uw aanmeldingen voor %s fondsen zijn geweigerd om producten in de webshop te plaatsen.",
                $logsRejectedProducts->count()
            ));

            $mailBody->text(sprintf(
                "Dit betekent dat u voor de volgende fondsen geen producten meer in de webshop kunt plaatsen:\n- %s",
                implode("\n- ", $logsRejectedProducts->pluck('fund_name')->toArray())
            ));

            $fundsWithSomeProducts = $organization->fund_providers()->whereKey(
                $logsRejectedProducts->pluck('fund_id')->unique()->toArray()
            )->whereHas('fund_provider_products')->pluck('fund_id')->unique();

            if ($fundsWithSomeProducts->count() > 0) {
                $mailBody->text(sprintf(
                    "Voor deze fondsen staan nog specifieke producten in de webshop:\n- %s",
                    implode("\n- ", Fund::whereKey($fundsWithSomeProducts)->pluck('name')->toArray())
                ));
            }

            $mailBody->text("Bekijk het dashboard voor de volledige context en huidige status.");
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
            $mailBody->h3("Een aantal van uw producten zijn goedgekeurd voor fondsen.");
            $mailBody->text("Voor elk fonds zijn specifieke rechten aan u toegekend. \nBekijk het dashboard voor de volledige context en status.\n");

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
                "Feedback op %s product(en)",
                $logsProductsFeedback->count()
            ));

            $mailBody->text(sprintf(
                "U heeft feedback ontvangen op %s product(en).",
                $logsProductsFeedback->count()
            ));

            foreach ($logsProductsFeedback as $logsProductFeedback) {
                $mailBody->h5(sprintf(
                    "Nieuwe berichten op %s voor €%s",
                    $logsProductFeedback[0]['product_name'],
                    $logsProductFeedback[0]['product_price_locale']
                ), ['margin_less']);

                foreach ($logsProductFeedback->groupBy('fund_id') as $logsProductFeedbackLog) {
                    $mailBody->text(sprintf(
                        "- %s - heeft %s bericht(en) gestuurd op uw aanmelding voor %s.\n",
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