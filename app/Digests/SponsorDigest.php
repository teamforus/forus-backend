<?php

namespace App\Digests;

use App\Mail\Digest\BaseDigestMail;
use App\Mail\Digest\DigestSponsorMail;
use App\Mail\MailBodyBuilder;
use App\Models\Fund;
use App\Models\FundProvider;
use App\Models\FundProviderProduct;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\Product;
use App\Scopes\Builders\FundProviderQuery;
use App\Scopes\Builders\ProductQuery;
use App\Services\EventLogService\Models\EventLog;
use App\Services\Forus\Notification\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Class SponsorDigest
 * @package App\Digests
 */
class SponsorDigest extends BaseOrganizationDigest
{
    use Dispatchable;

    protected string $requiredRelation = 'funds';
    protected string $digestKey = 'sponsor';
    protected array $employeePermissions = [
        'manage_providers'
    ];

    /**
     * @param Organization $organization
     * @param NotificationService $notificationService
     */
    public function handleOrganizationDigest(
        Organization $organization,
        NotificationService $notificationService
    ): void {
        $emailBody = new MailBodyBuilder();
        $emailBody->h1(trans('digests/sponsor.title'));
        $emailBody->text(trans('digests/sponsor.greetings', [
            'organization_name' => $organization->name,
        ]), ["margin_less"]);

        $groups = collect([
            $this->getApplicationsEmailBody($organization, false),
            $this->getApplicationsEmailBody($organization, true),
            $this->getProductsUnsubscriptionsEmailBody($organization),
            $this->getProductsPendingEmailBody($organization),
            $this->getProductsApprovedEmailBody($organization, false),
            $this->getProductsApprovedEmailBody($organization, true),
            $this->getProvidersReplyEmailBody($organization),
        ])->filter();

        if ($groups->count() > 0) {
            $emailBody = $groups->reduce(function (MailBodyBuilder $emailBody, MailBodyBuilder $group) {
                return $emailBody->merge($group);
            }, $emailBody);

            $emailBody->separator();
            $emailBody->button_primary(
                Implementation::general()->urlSponsorDashboard(),
                trans('digests/sponsor.dashboard_button')
            );

            $this->sendOrganizationDigest($organization, $emailBody, $notificationService);
        }
    }

    /**
     * @param Organization $organization
     * @param bool $approved
     * @return MailBodyBuilder|null
     */
    private function getApplicationsEmailBody(
        Organization $organization,
        bool $approved
    ): ?MailBodyBuilder {
        $emailBody = new MailBodyBuilder();
        $transKey = $approved ? 'digests/sponsor.providers_approved' : 'digests/sponsor.providers_pending';

        $fundProvidersList = $organization->funds_active->map(function(Fund $fund) use ($organization, $approved) {
            $query = FundProvider::query()
                ->where('fund_id', $fund->id)
                ->where('created_at', '>=', $this->getLastOrganizationDigestTime($organization))
                ->with('organization');

            if ($approved) {
                FundProviderQuery::whereApprovedForFundsFilter($query, $fund->id);
            } else {
                FundProviderQuery::whereDeclinedForFundsFilter($query, $fund->id);
            }

            return [$fund, $query->count(), $query->get()];
        });

        if ($fundProvidersList->sum(1) < 1) {
            return null;
        }

        $emailBody->separator();
        $emailBody->h2(trans("$transKey.title"));

        /** @var Fund $fund */
        /** @var int $count */
        /** @var Collection|FundProvider $fundProviders */
        foreach ($fundProvidersList as [$fund, $count, $fundProviders]) {
            if ($count < 1) {
                continue;
            }

            $emailBody->h3(trans("$transKey.header", [
                'fund_name' => $fund->name,
            ]), ['margin_less']);

            $emailBody->text(trans_choice("$transKey.details", $count, [
                'fund_name' => $fund->name,
                'providers_count' => $count,
                'providers_list' => $fundProviders->pluck('organization.name')->implode("\n- "),
            ]));
        }

        return $emailBody;
    }

    /**
     * @param Organization $organization
     * @return MailBodyBuilder|null
     */
    private function getProductsUnsubscriptionsEmailBody(Organization $organization): ?MailBodyBuilder
    {
        $emailBody = new MailBodyBuilder();

        $fundProvidersList = $organization->funds_active->map(function(Fund $fund) use ($organization) {
            $query = FundProvider::query()
                ->where('fund_id', $fund->id)
                ->where(fn (Builder $builder) => FundProviderQuery::whereApprovedForFundsFilter($builder, $fund->id))
                ->with('organization');

            $query->whereHas('fund_unsubscribes_active', function(Builder $builder) use ($organization) {
                $builder->where('created_at', '>=', $this->getLastOrganizationDigestTime($organization));
            });

            return [$fund, $query->count(), $query->get()];
        });

        if ($fundProvidersList->sum(1) < 1) {
            return null;
        }

        $emailBody->separator();
        $emailBody->h2(trans("digests/sponsor.providers_unsubscriptions.title"));

        /** @var Fund $fund */
        /** @var int $count */
        /** @var Collection|FundProvider $fundProviders */
        foreach ($fundProvidersList as [$fund, $count, $fundProviders]) {
            if ($count < 1) {
                continue;
            }

            $emailBody->h3(trans("digests/sponsor.providers_unsubscriptions.header", [
                'fund_name' => $fund->name,
            ]), ['margin_less']);

            $emailBody->text(trans_choice("digests/sponsor.providers_unsubscriptions.details", $count, [
                'fund_name' => $fund->name,
                'providers_count' => $count,
                'providers_list' => $fundProviders->pluck('organization.name')->implode("\n- "),
            ]));
        }

        return $emailBody;
    }

    /**
     * @param Organization $organization
     * @param bool $manuallyApproved
     * @return MailBodyBuilder|null
     */
    private function getProductsApprovedEmailBody(
        Organization $organization,
        bool $manuallyApproved,
    ): ?MailBodyBuilder {
        $transKey = $manuallyApproved ? 'digests/sponsor.products_manual' : 'digests/sponsor.products_auto';

        $productsList = $organization->funds_active->map(function(Fund $fund) use ($organization, $manuallyApproved) {
            $providerQuery = FundProviderQuery::whereApprovedForFundsFilter(FundProvider::query(), $fund->id);

            $query = Product::query()
                ->whereIn('organization_id', $providerQuery->select('organization_id'))
                ->where('created_at', '>=', $this->getLastOrganizationDigestTime($organization))
                ->where(fn (Builder $builder) => ProductQuery::approvedForFundsFilter($builder, $fund->id))
                ->where(fn (Builder $builder) => ProductQuery::inStockAndActiveFilter($builder))
                ->with('organization');

            $query->whereHas('fund_provider_products', function (Builder|FundProviderProduct $builder) use ($fund) {
                $builder->whereRelation('fund_provider', 'fund_id', $fund->id);
            }, $manuallyApproved ? '>' : '=', 0);

            return [$fund, $query->count(), $query->get()];
        });

        if ($productsList->sum(1) < 1) {
            return null;
        }

        return $this->buildProductsList($productsList, $transKey);
    }

    /**
     * @param Organization $organization
     * @return MailBodyBuilder|null
     */
    private function getProductsPendingEmailBody(Organization $organization): ?MailBodyBuilder
    {
        $transKey = 'digests/sponsor.products_pending';

        $productsList = $organization->funds_active->map(function(Fund $fund) use ($organization) {
            $providerQuery = FundProviderQuery::whereApprovedForFundsFilter(
                FundProvider::query(), $fund->id,
            )->select('organization_id');

            $query = Product::query()
                ->whereIn('organization_id', $providerQuery)
                ->where('created_at', '>=', $this->getLastOrganizationDigestTime($organization))
                ->where(fn (Builder $builder) => ProductQuery::inStockAndActiveFilter($builder))
                ->where(fn (Builder $builder) => ProductQuery::notApprovedForFundsFilter($builder, $fund->id))
                ->with('organization');

            return [$fund, $query->count(), $query->get()];
        });

        if ($productsList->sum(1) < 1) {
            return null;
        }

        return $this->buildProductsList($productsList, $transKey);
    }

    /**
     * @param SupportCollection $productsList
     * @param string $transKey
     * @return MailBodyBuilder
     */
    private function buildProductsList(
        SupportCollection $productsList,
        string $transKey
    ): MailBodyBuilder {
        $emailBody = new MailBodyBuilder();
        $emailBody->separator();
        $emailBody->h2(trans("$transKey.title"));

        /** @var Fund $fund */
        /** @var int $count */
        /** @var Collection|Product[] $products */
        foreach ($productsList as [$fund, $count, $products]) {
            if ($count < 1) {
                continue;
            }

            $transData = [
                'fund_name' => $fund->name,
                'products_count' => $count,
            ];

            $emailBody->h3(trans("$transKey.header", $transData), ['margin_less']);
            $emailBody->text(trans_choice("$transKey.details", $count, $transData));

            /** @var Collection|Product[] $groupProduct */
            foreach ($products->groupBy('organization_id') as $groupProduct) {
                $emailBody->h5(trans_choice("$transKey.provider", $groupProduct->count(), array_merge($transData, [
                    'provider_name' => $groupProduct[0]->organization->name,
                    'products_count' => $groupProduct->count(),
                ])), ['margin_less']);

                $emailBody->text("- " . $groupProduct->map(function (Product $product) use ($transKey) {
                    return trans("$transKey.item", array_merge([
                        'product_name' => $product->name,
                        'product_price_locale' => $product->price_locale,
                    ]));
                })->implode("\n- "));
            }
        }

        return $emailBody;
    }

    /**
     * @param Organization $organization
     * @return MailBodyBuilder|null
     */
    private function getProvidersReplyEmailBody(Organization $organization): ?MailBodyBuilder
    {
        $events = [];
        $emailBody = new MailBodyBuilder();

        foreach ($organization->funds_active as $fund) {
            $query = EventLog::eventsOfTypeQuery(Fund::class, $fund->id);
            $query->where('event', Fund::EVENT_PROVIDER_REPLIED);
            $query->where('created_at', '>=', $this->getLastOrganizationDigestTime($organization));
            $events[] = [$fund, $query->count(), $query->get()];
        }

        $total_messages = array_sum(array_pluck($events, '1'));

        if ($total_messages > 0) {
            $emailBody->separator();
            $emailBody->h2(trans('digests/sponsor.feedback.title'));

            /** @var Fund $fund */
            /** @var int $countEvents */
            /** @var EventLog[]|Collection $eventLogs */
            foreach ($events as [$fund, $countEvents, $eventLogs]) {
                if ($countEvents < 1) {
                    continue;
                }

                $emailBody->h3(trans_choice('digests/sponsor.feedback.header', $countEvents, [
                    'count_messages' => $countEvents,
                    'fund_name' => $fund->name,
                ]));

                $logsByProvider = $eventLogs->pluck('data')->groupBy('provider_id');

                foreach ($logsByProvider as $logs) {
                    $logsByProduct = collect($logs)->groupBy('product_id');
                    $emailBody->h5(trans("digests/sponsor.feedback.item_header", $logs[0]), [
                        'margin_less'
                    ]);

                    foreach ($logsByProduct as $_logsByProduct) {
                        $emailBody->text(trans_choice('digests/sponsor.feedback.item', count(
                            $logsByProduct
                        ), array_merge([
                            'count_messages' => count($logsByProduct)
                        ], $_logsByProduct[0])));
                    }
                }

                $emailBody = $emailBody->space();
            }

            return $emailBody;
        }

        return null;
    }

    /**
     * @param MailBodyBuilder $emailBody
     * @return BaseDigestMail
     */
    protected function getDigestMailable(MailBodyBuilder $emailBody): BaseDigestMail
    {
        return new DigestSponsorMail(compact('emailBody'));
    }
}