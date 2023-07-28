<?php

namespace App\Digests;

use App\Mail\Digest\BaseDigestMail;
use App\Mail\Digest\DigestProviderFundsMail;
use App\Mail\MailBodyBuilder;
use App\Models\Implementation;
use App\Models\Organization;
use App\Models\ProductReservation;
use App\Services\EventLogService\Models\EventLog;
use App\Services\Forus\Notification\NotificationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Collection;

class ProviderReservationsDigest extends BaseOrganizationDigest
{
    use Dispatchable;

    protected string $requiredRelation = 'products.product_reservations';
    protected string $digestKey = 'provider_reservations';

    protected array $employeePermissions = [
        'manage_products',
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
            $this->getOrganizationProductsReservedMailBody($organization)
        );

        if ($mailBody->count() === 1) {
            $this->updateLastDigest($organization);
            return;
        }

        $mailBody->text(trans('digests/provider_reservations.description', [
            'provider_name' => $organization->name,
        ]));

        $mailBody->space()->button_primary(
            Implementation::general()->url_provider,
            trans('digests/provider_funds.dashboard_button')
        );

        $this->sendOrganizationDigest($organization, $mailBody, $notificationService);
    }

    /**
     * @param Organization $organization
     * @param array $targetEvents
     * @param array $otherEvents
     * @return Collection
     */
    protected function getEvents(
        Organization $organization,
        array $targetEvents,
        array $otherEvents
    ): Collection {
        // reservations query
        $reservationQuery = ProductReservation::where(function(Builder $builder) use ($organization) {
            $builder->whereHas('product', function(Builder $builder) use ($organization) {
                $builder->whereIn('id', $organization->products()->getQuery()->select('id'));
            });
        });

        // logs for selected period
        $logsApprovedReservations = EventLog::eventsOfTypeQuery(ProductReservation::class, $reservationQuery)
            ->whereIn('event', $otherEvents)
            ->where('created_at', '>=', $this->getLastOrganizationDigestTime($organization))
            ->get()
            ->groupBy('loggable_id');

        return $logsApprovedReservations->filter(static function(Collection $group) use ($targetEvents) {
            return in_array($group->sortBy('created_at')->last()->event, $targetEvents);
        })->map(static function(Collection $group) {
            return $group->sortBy('created_at')->last();
        })->flatten(1)->pluck('data');
    }

    /**
     * @param Organization $organization
     * @return MailBodyBuilder
     */
    private function getOrganizationProductsReservedMailBody(
        Organization $organization
    ): MailBodyBuilder {
        $mailBody = new MailBodyBuilder();

        // only those created by requester and with state created or accepted
        $logsProducts = $this->getEvents($organization, [
            ProductReservation::EVENT_ACCEPTED,
            ProductReservation::EVENT_CREATED,
            ProductReservation::EVENT_REJECTED,
            ProductReservation::EVENT_CANCELED_BY_CLIENT,
            ProductReservation::EVENT_CANCELED_BY_PROVIDER,
        ], ProductReservation::EVENTS);

        $grouped = $logsProducts->groupBy('product_id');

        if ($logsProducts->count() > 0) {
            $count_reservations = $logsProducts->count();
            $count_pending_reservations = $logsProducts->where(
                'product_reservation_state', ProductReservation::STATE_PENDING
            )->count();
            $provider_name = $organization->name;

            $mailBody->text(trans_choice(
                "digests/provider_reservations.reservations.title",
                $count_reservations,
                compact('count_reservations', 'provider_name', 'count_pending_reservations')
            ));

            foreach ($grouped as $group) {
                /** @var Collection $groups */
                $groups = $group->groupBy('product_reservation_state');
                $states = [
                    ProductReservation::STATE_PENDING  => 'in afwachting',
                    ProductReservation::STATE_ACCEPTED => 'geaccepteerd',
                    ProductReservation::STATE_REJECTED => 'geweigerd',
                    ProductReservation::STATE_CANCELED_BY_CLIENT => 'geannuleerd door aanvrager',
                    ProductReservation::STATE_CANCELED_BY_PROVIDER => 'geannuleerd',
                ];

                $mailBody->h5(trans("digests/provider_reservations.reservations.product_item.title", [
                    'product_name' => $group[0]['product_name'] ?? '',
                    'product_price_locale' => $group[0]['product_price_locale'] ?? '',
                ]), ['margin_less']);

                $per_state_text = trans(
                    "digests/provider_reservations.reservations.product_item.description_total", [
                        'count_total' => count($group),
                    ]
                );

                foreach ($states as $stateKey => $stateName) {
                    $count_per_state = count($groups[$stateKey] ?? []);

                    if ($count_per_state) {
                        $per_state_text .= "\n".trans(
                            "digests/provider_reservations.reservations.product_item.description_per_state", [
                                'state_name' => $stateName,
                                'count_per_state' => $count_per_state,
                            ]
                        );
                    }
                }

                $mailBody->text($per_state_text);
            }
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
