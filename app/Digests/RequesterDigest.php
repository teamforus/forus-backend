<?php

namespace App\Digests;

use App\Mail\Digest\DigestRequesterMail;
use App\Mail\MailBodyBuilder;
use App\Models\Fund;
use App\Models\Voucher;
use App\Services\EventLogService\Models\EventLog;
use App\Models\Identity;
use App\Services\Forus\Notification\NotificationService;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

class RequesterDigest
{
    use Dispatchable;

    /**
     * @param Fund $fund
     * @param string $targetEvent
     * @param array $otherEvents
     */
    protected function getEvents(
        Fund $fund,
        string $targetEvent,
        array $otherEvents
    ): Collection {
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
     * @param Arrayable $funds
     *
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
     * @param Arrayable $funds
     *
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
                        return trans('digests/requester.products.price', $data);
                    })->toArray();
                    $array[$fund->id]->text(implode("\n", $textList) . "\n");
                }
            }

            return $array;
        }, []);
    }

    /**
     * @return (Identity|\App\Models\BaseModel|mixed)[][]
     *
     * @psalm-return list{0?: array{identity: Identity|\App\Models\BaseModel, funds: mixed},...}
     */
    private function getIdentitiesWithFunds(): array
    {
        $identityFunds = [];

        $vouchers = Voucher::where(static function(Builder $builder) {
            $builder->whereNull('product_id');
        })->orWhere(static function(Builder $builder) {
            $builder->whereNotNull('product_id');
            $builder->doesntHave('transactions');
        })->get()->filter(static function(Voucher $voucher) {
            return !$voucher->used;
        });

        $vouchersByIdentities = $vouchers->groupBy('identity_address');

        foreach ($vouchersByIdentities as $identity_address => $vouchersByIdentity) {
            $identity = Identity::findByAddress($identity_address);

            if (!empty($identity) && $identity->email) {
                $funds = $vouchersByIdentity->pluck('fund_id')->unique()->toArray();
                $identityFunds[] = compact('identity', 'funds');
            }
        }

        return $identityFunds;
    }

    /**
     * @param Fund $fund
     */
    public function getFundDigestTime(Fund $fund): \Illuminate\Support\Carbon
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