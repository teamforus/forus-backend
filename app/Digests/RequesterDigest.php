<?php

namespace App\Digests;

use App\Mail\Digest\DigestRequesterMail;
use App\Mail\MailBodyBuilder;
use App\Models\Fund;
use App\Models\Identity;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use App\Services\Forus\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

class RequesterDigest extends BaseDigest
{
    use Dispatchable;

    /**
     * @param NotificationService $notificationService
     */
    public function handle(NotificationService $notificationService): void
    {
        $funds = Fund::whereState(Fund::STATE_ACTIVE)->get()->keyBy('id');

        $fundsProvidersBody = collect($this->buildFundProvidersMailBody($funds));
        $fundsProductsBody = collect($this->buildFundProductsMailBody($funds));

        foreach ($funds as $fund) {
            $this->updateLastDigest($fund);
        }

        foreach ($this->getIdentitiesWithFunds() as $identityFunds) {
            /** @var Identity $identity */
            $identity = $identityFunds['identity'];

            $emailBody = new MailBodyBuilder();
            $emailBody->h1(trans('digests/requester.title'));

            foreach ($identityFunds['funds'] as $fundId) {
                if (isset($fundsProvidersBody[$fundId])) {
                    $emailBody = $emailBody->merge($fundsProvidersBody[$fundId]);

                    if (isset($funds[$fundId]->fund_config->implementation)) {
                        $emailBody->button_primary(
                            $funds[$fundId]->fund_config->implementation->urlWebshop(),
                            trans('digests/requester.button_webshop')
                        )->space();
                    }
                }
            }

            if ($emailBody->count() > 1) {
                $emailBody->separator();
            }

            foreach ($identityFunds['funds'] as $fundId) {
                if (isset($fundsProductsBody[$fundId])) {
                    $emailBody = $emailBody->merge($fundsProductsBody[$fundId]);

                    if (isset($funds[$fundId]->fund_config->implementation)) {
                        $emailBody->button_primary(
                            $funds[$fundId]->fund_config->implementation->urlWebshop(),
                            trans('digests/requester.button_webshop')
                        )->space();
                    }
                }
            }

            if ($emailBody->count() > 1) {
                $emailBody->pop();
                $notificationService->sendDigest(
                    $identity->email,
                    new DigestRequesterMail(compact('emailBody'))
                );
            }
        }
    }

    /**
     * @param Fund $fund
     * @param string $targetEvent
     * @param array $otherEvents
     * @return \Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Collection|Collection
     */
    protected function getEvents(
        Fund $fund,
        string $targetEvent,
        array $otherEvents
    ): mixed {
        $query = EventLog::eventsOfTypeQuery(Fund::class, $fund->id);

        $logsApprovedBudget = $query->whereIn('event', $otherEvents)->where(
            'created_at', '>=', $this->getFundDigestTime($fund)
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
     * @param EloquentCollection|Fund[] $funds
     * @return MailBodyBuilder[]
     */
    public function buildFundProvidersMailBody(EloquentCollection|Arrayable $funds): array
    {
        return $funds->reduce(function($array, Fund $fund) {
            $events = $this->getEvents($fund, Fund::EVENT_PROVIDER_APPROVED_BUDGET, [
                Fund::EVENT_PROVIDER_APPROVED_BUDGET,
                Fund::EVENT_PROVIDER_REVOKED_BUDGET,
            ]);

            if ($events->count() > 0) {
                $array[$fund->id] = MailBodyBuilder::create();
                $array[$fund->id]->h3(trans_choice(
                    'digests/requester.providers.title', $events->count(), [
                        'count_providers' => $events->count(),
                        'sponsor_name' => $fund->organization->name,
                        'fund_name' => $fund->name
                    ]));

                $array[$fund->id]->text(trans(
                    'digests/requester.providers.description', [
                        'providers_list' => $events->pluck('provider_name')->implode("\n- ")
                    ]));
            }

            return $array;
        }, []);
    }

    /**
     * @param EloquentCollection|Fund[] $funds
     * @return MailBodyBuilder[]
     */
    private function buildFundProductsMailBody(EloquentCollection|Arrayable $funds): array
    {
        return $funds->reduce(function($array, Fund $fund) {
            $events = $this->getEvents($fund, Fund::EVENT_PRODUCT_APPROVED, [
                Fund::EVENT_PRODUCT_APPROVED,
                Fund::EVENT_PRODUCT_REVOKED,
            ])->merge($this->getEvents($fund, Fund::EVENT_PRODUCT_ADDED, [
                Fund::EVENT_PRODUCT_ADDED,
            ]));

            if ($events->count() > 0) {
                $productEventsByProvider = $events->groupBy('provider_id');

                $array[$fund->id] = new MailBodyBuilder();
                $array[$fund->id]->h3(trans_choice(
                    'digests/requester.products.title', $events->count(), [
                    'count_products' => $events->count(),
                    'sponsor_name' => $fund->organization->name,
                    'fund_name' => $fund->name
                ]));

                foreach ($productEventsByProvider as $productEventByProvider) {
                    $array[$fund->id]->h5($productEventByProvider[0]['provider_name']);
                    $textList = $productEventByProvider->map(static function(array $data) {
                        return trans(
                            'digests/requester.products.price',
                            self::arrayOnlyString($data)
                        );
                    })->toArray();
                    $array[$fund->id]->text(implode("\n", $textList) . "\n");
                }
            }

            return $array;
        }, []);
    }

    /**
     * @return array
     */
    private function getIdentitiesWithFunds(): array
    {
        $identityFunds = [];

        /** @var Collection|Voucher[] $vouchers */
        $vouchers = Voucher::where(static function(Builder $builder) {
            $builder->whereNull('product_id');
        })->orWhere(static function(Builder $builder) {
            $builder->whereNotNull('product_id');
            $builder->doesntHave('transactions');
        })->get()->filter(static function(Voucher $voucher) {
            return !$voucher->used;
        });

        $vouchersByIdentities = $vouchers->groupBy('identity_id');

        foreach ($vouchersByIdentities as $identity_id => $vouchersByIdentity) {
            $identity = Identity::firstWhere('id', $identity_id);

            if (!empty($identity?->email)) {
                $funds = $vouchersByIdentity->pluck('fund_id')->unique()->toArray();
                $identityFunds[] = compact('identity', 'funds');
            }
        }

        return $identityFunds;
    }

    /**
     * @param Fund $fund
     * @return Carbon
     */
    public function getFundDigestTime(Fund $fund): Carbon
    {
        return $fund->lastDigestOfType('requester')->created_at ?? now()->subWeek();
    }

    /**
     * @param Fund $fund
     */
    protected function updateLastDigest(Fund $fund): void {
        $fund->digests()->create([
            'type' => 'requester'
        ]);
    }
}