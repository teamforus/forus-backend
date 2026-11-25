<?php

namespace Tests\Feature;

use App\Mail\ProductReservations\ProductReservationAcceptedMail;
use App\Models\ProductReservation;
use App\Models\SystemNotification;
use App\Notifications\Identities\ProductReservation\IdentityProductReservationAcceptedNotification;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Mockery\VerificationDirector;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Tests\Traits\MakesTestOrganizations;
use Throwable;

class NotificationTransactionTest extends TestCase
{
    use DatabaseTransactions;
    use MakesProductReservations;
    use MakesTestFunds;
    use MakesTestOrganizations;

    /**
     * @throws Throwable
     * @return void
     */
    public function testReservationNotificationIsDeliveredOnlyAfterCommit(): void
    {
        $reservation = $this->makeNotificationReservation();
        $identity = $reservation->voucher->identity;
        $level = DB::transactionLevel();
        DB::beginTransaction();

        try {
            $this->apiAcceptProductReservationByProviderRequest(
                $reservation,
                $reservation->product->organization->identity,
            )->assertOk();

            $this->assertSame(ProductReservation::STATE_ACCEPTED, $reservation->refresh()->state);
            $this->assertNoAcceptedNotification($reservation);
            DB::commit();
        } finally {
            DB::rollBack($level);
        }

        $this->assertSame(ProductReservation::STATE_ACCEPTED, $reservation->refresh()->state);
        $this->assertMailableSent($identity->email, ProductReservationAcceptedMail::class);

        $this->assertSame(
            1,
            $this->getEmailOfTypeQuery($identity->email, ProductReservationAcceptedMail::class)->count(),
        );

        $this->assertSame(1, $identity->notifications()
            ->where('type', IdentityProductReservationAcceptedNotification::class)->count());

        $event = $reservation->logs()->where('event', ProductReservation::EVENT_ACCEPTED)->sole();

        $notification = $identity->notifications()
            ->where('type', IdentityProductReservationAcceptedNotification::class)->sole();

        $this->assertSame($event->id, $notification->data['event_id']);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testReservationRollbackSuppressesNotifications(): void
    {
        $reservation = $this->makeNotificationReservation();
        $before = $reservation->getAttributes();
        $level = DB::transactionLevel();
        DB::beginTransaction();

        try {
            $this->apiAcceptProductReservationByProviderRequest(
                $reservation,
                $reservation->product->organization->identity,
            )->assertOk();

            $this->assertSame(ProductReservation::STATE_ACCEPTED, $reservation->refresh()->state);
            $this->assertSame(1, $reservation->logs()->where('event', ProductReservation::EVENT_ACCEPTED)->count());
            $this->assertNoAcceptedNotification($reservation);
        } finally {
            DB::rollBack($level);
        }

        $this->assertSame($before, $reservation->refresh()->getAttributes());
        $this->assertFalse($reservation->logs()->where('event', ProductReservation::EVENT_ACCEPTED)->exists());
        $this->assertNull($reservation->voucher_transaction);
        $this->assertNoAcceptedNotification($reservation);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testNotificationDispatchFailureDoesNotUndoCommittedAction(): void
    {
        $reservation = $this->makeNotificationReservation();
        $dispatcher = Notification::getFacadeRoot();
        $logger = Log::getFacadeRoot();
        $stateAtDispatch = null;
        $levelAtDispatch = null;
        $logSpy = Log::spy();

        try {
            Notification::partialMock()->shouldReceive('send')->andReturnUsing(
                function (mixed $identities, mixed $notification) use (
                    $dispatcher,
                    $reservation,
                    &$stateAtDispatch,
                    &$levelAtDispatch,
                ): void {
                    if ($notification instanceof IdentityProductReservationAcceptedNotification) {
                        $stateAtDispatch = $reservation->fresh()->state;
                        $levelAtDispatch = DB::transactionLevel();

                        throw new RuntimeException('Injected notification dispatch failure.');
                    }

                    $dispatcher->send($identities, $notification);
                },
            );

            $this->apiAcceptProductReservationByProviderRequest(
                $reservation,
                $reservation->product->organization->identity,
            )->assertOk();

            $this->assertSame(1, $levelAtDispatch);
            $this->assertSame(ProductReservation::STATE_ACCEPTED, $stateAtDispatch);
            $this->assertSame(ProductReservation::STATE_ACCEPTED, $reservation->refresh()->state);
            $this->assertNotNull($reservation->voucher_transaction);
            $this->assertNoAcceptedNotification($reservation);

            /** @var VerificationDirector $verification */
            $verification = $logSpy->shouldHaveReceived('error');

            $verification->withArgs(fn (string $message): bool =>
                str_contains($message, 'Injected notification dispatch failure.'))->once();
        } finally {
            Notification::swap($dispatcher);
            Log::swap($logger);
        }
    }

    /**
     * @throws Throwable
     * @return ProductReservation
     */
    private function makeNotificationReservation(): ProductReservation
    {
        Config::set('queue.default', 'sync');
        ['reservation' => $reservation] = $this->makeReservationContext();
        $notification = SystemNotification::findByKey(IdentityProductReservationAcceptedNotification::getKey());
        $notification->update(['mail' => true, 'database' => true, 'push' => false]);

        $notification->updateConfig($reservation->voucher->fund->fund_config->implementation, [
            'enable_all' => true, 'enable_mail' => true, 'enable_database' => true, 'enable_push' => false,
        ]);

        $this->assertSame(ProductReservation::STATE_PENDING, $reservation->state);

        return $reservation->refresh();
    }

    /**
     * @param ProductReservation $reservation
     * @return void
     */
    private function assertNoAcceptedNotification(ProductReservation $reservation): void
    {
        $identity = $reservation->voucher->identity;

        $this->assertMailableNotSent($identity->email, ProductReservationAcceptedMail::class);

        $this->assertFalse($identity->notifications()->where([
            'type' => IdentityProductReservationAcceptedNotification::class,
        ])->exists());
    }
}
