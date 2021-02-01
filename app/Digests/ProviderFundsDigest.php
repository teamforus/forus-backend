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
        $mailBody->h1(trans('digests/provider_funds.title', [
            'provider_name' => $organization->name,
        ]));

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
            Implementation::general()->url_provider,
            trans('digests/provider_funds.dashboard_button')
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
            $count_funds = $logsApprovedBudget->count();
            $mailBody->h3(trans_choice(
                "digests/provider_funds.budget_approved.title",
                $count_funds,
                compact('count_funds')
            ));

            $mailBody->text(trans_choice(
                'digests/provider_funds.budget_approved.funds_list',
                    $logsApprovedBudget->count()
                ) . "\n- " . $logsApprovedBudget->pluck('fund_name')->implode("\n- ")
            );

            $mailBody->text(trans('digests/provider_funds.budget_approved.details'));
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
            $count_funds = $logsApprovedProducts->count();
            $mailBody->h3(trans_choice(
                "digests/provider_funds.products_approved.title",
                $count_funds,
                compact('count_funds')
            ));

            $mailBody->text(trans_choice(
                'digests/provider_funds.products_approved.funds_list',
                $logsApprovedProducts->count()
                ) . "\n- " . $logsApprovedProducts->pluck('fund_name')->implode("\n- ")
            )->separator();
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
            $count_funds = $logsRejectedBudget->count();
            $mailBody->h3(trans_choice(
                "digests/provider_funds.budget_revoked.title",
                $count_funds,
                compact('count_funds')
            ));

            $mailBody->text(trans_choice(
                'digests/provider_funds.budget_revoked.funds_list',
                $logsRejectedBudget->count()
                ) . "\n - " . $logsRejectedBudget->pluck('fund_name')->implode("\n- ")
            );

            $mailBody->text(trans('digests/provider_funds.budget_revoked.details'));
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
            $count_funds = $logsRejectedProducts->count();
            $mailBody->h3(sprintf(trans_choice(
                'digests/provider_funds.products_revoked.title',
                $count_funds,
                compact('count_funds')
            )));

            $mailBody->text(trans_choice(
                'digests/provider_funds.products_revoked.funds_list',
                $logsRejectedProducts->count()
                ) . "\n- " . $logsRejectedProducts->pluck('fund_name')->implode("\n- "));

            $fundsWithSomeProducts = $organization->fund_providers()->whereKey(
                $logsRejectedProducts->pluck('fund_id')->unique()->toArray()
            )->whereHas('fund_provider_products')->pluck('fund_id')->unique();
            $fundsWithSomeProducts = Fund::whereKey($fundsWithSomeProducts)->pluck('name');

            if ($fundsWithSomeProducts->count() > 0) {
                $mailBody->text(trans_choice(
                    'digests/provider_funds.products_revoked.funds_list_individual',
                    $fundsWithSomeProducts->toArray()
                    ) . "\n- " . $fundsWithSomeProducts->implode("\n- "));
            }

            $mailBody->text(trans('digests/provider_funds.products_revoked.details'));
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
            $mailBody->h3(trans("digests/provider_funds.individual_products.title"));
            $mailBody->text(trans("digests/provider_funds.individual_products.details"));

            foreach ($logsProductsApproved->groupBy('fund_id') as $logsProductApproved) {
                $mailBody->h5($logsProductApproved[0]['fund_name'], ['margin_less']);
                $mailBody->text(implode("\n", array_map(static function($log) {
                    return trans('digests/provider_funds.individual_products.product', $log);
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
            $count_products = $logsProductsFeedback->count();
            $mailBody->h3(trans_choice('digests/provider_funds.feedback.title',
                $count_products,
                compact('count_products')
            ));
            $mailBody->text(trans_choice('digests/provider_funds.feedback.details',
                $count_products,
                compact('count_products')
            ));

            foreach ($logsProductsFeedback as $logsProductFeedback) {
                $mailBody->h5(trans(
                    'digests/provider_funds.feedback.product_title',
                    $logsProductFeedback[0]
                ), ['margin_less']);

                foreach ($logsProductFeedback->groupBy('fund_id') as $logsProductFeedbackLog) {
                    $count_messages = $logsProductFeedbackLog->count();
                    $mailBody->text(trans_choice(
                        'digests/provider_funds.feedback.product_details',
                        $count_messages,
                        array_merge($logsProductFeedback[0], compact('count_messages'))
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
