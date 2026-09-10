<?php

namespace Tests\Feature;

use App\Mail\ProductReservations\ProductReservationProviderMessageMail;
use App\Models\FundProvider;
use App\Models\Identity;
use App\Models\ProductReservation;
use App\Models\ProviderMessage;
use App\Services\MailDatabaseLoggerService\Traits\AssertsSentEmails;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Tests\Traits\MakesApiRequests;
use Tests\Traits\MakesProductReservations;
use Tests\Traits\MakesTestFunds;
use Throwable;

class ProviderMessageTest extends TestCase
{
    use AssertsSentEmails;
    use DatabaseTransactions;
    use MakesApiRequests;
    use MakesProductReservations;
    use MakesTestFunds;

    /**
     * @throws Throwable
     * @return void
     */
    public function testRegularProviderMessagesRequireEnabledAcceptedFundProvider(): void
    {
        [
            'reservation' => $reservation,
            'fundProvider' => $fundProvider,
            'identity' => $identity,
        ] = $this->makeProviderMessageContext();

        $this->apiStoreProductReservationProviderMessageRequest(
            $reservation,
            $identity,
            ['message' => 'Disabled regular message'],
        )->assertForbidden();

        $fundProvider->update(['allow_provider_messages' => true]);

        $response = $this->apiStoreProductReservationProviderMessageRequest(
            $reservation,
            $identity,
            ['message' => 'Allowed regular message'],
        )->assertSuccessful();

        $message = ProviderMessage::findOrFail($response->json('data.id'));

        $this->assertSame(ProviderMessage::TYPE_REGULAR_MESSAGE, $message->type);
        $fundProvider->setState(FundProvider::STATE_REJECTED);
        $this->assertTrue($fundProvider->refresh()->allow_provider_messages);

        $this->apiStoreProductReservationProviderMessageRequest(
            $reservation,
            $identity,
            ['message' => 'Rejected regular message'],
        )->assertForbidden();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRegularProviderMessageRequiresRequesterEmail(): void
    {
        [
            'reservation' => $reservation,
            'fundProvider' => $fundProvider,
            'identity' => $identity,
        ] = $this->makeProviderMessageContext(false);

        $fundProvider->update(['allow_provider_messages' => true]);

        $this->apiStoreProductReservationProviderMessageRequest(
            $reservation,
            $identity,
            ['message' => 'Message without a recipient'],
        )->assertUnprocessable()->assertJsonValidationErrors('message')->assertJsonPath(
            'errors.message.0',
            trans('validation.provider_message.recipient_email_missing'),
        );

        $this->assertSame(0, $reservation->provider_messages()->count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testApprovalMessageDoesNotRequireRegularProviderMessages(): void
    {
        [
            'reservation' => $reservation,
            'identity' => $identity,
        ] = $this->makeProviderMessageContext();

        $approveNote = 'Approval state message';

        $this->apiAcceptProductReservationByProviderRequest($reservation, $identity, [
            'note' => $approveNote,
            'share_note_by_email' => true,
        ])->assertSuccessful();

        $this->assertSame(
            ProviderMessage::TYPE_APPROVE_RESERVATION,
            $reservation->provider_messages()->where('message', $approveNote)->value('type'),
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testApprovalEmailSharingRequiresRequesterEmail(): void
    {
        [
            'reservation' => $reservation,
            'identity' => $identity,
        ] = $this->makeProviderMessageContext(false);

        $this->apiAcceptProductReservationByProviderRequest($reservation, $identity, [
            'note' => 'Approval note without a recipient',
            'share_note_by_email' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('share_note_by_email')->assertJsonPath(
            'errors.share_note_by_email.0',
            trans('validation.provider_message.recipient_email_missing'),
        );

        $this->assertTrue($reservation->refresh()->isPending());
        $this->assertSame(0, $reservation->provider_messages()->count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectionEmailSharingRequiresRequesterEmail(): void
    {
        [
            'reservation' => $reservation,
            'identity' => $identity,
        ] = $this->makeProviderMessageContext(false);

        $this->apiCancelProductReservationByProviderRequest($reservation, $identity, [
            'note' => 'Rejection note without a recipient',
            'share_note_by_email' => true,
        ])->assertUnprocessable()->assertJsonValidationErrors('share_note_by_email')->assertJsonPath(
            'errors.share_note_by_email.0',
            trans('validation.provider_message.recipient_email_missing'),
        );

        $this->assertTrue($reservation->refresh()->isPending());
        $this->assertSame(0, $reservation->provider_messages()->count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testApprovalNoteWithoutEmailSharingDoesNotRequireRequesterEmail(): void
    {
        [
            'reservation' => $reservation,
            'identity' => $identity,
        ] = $this->makeProviderMessageContext(false);

        $note = 'Internal approval note';

        $this->apiAcceptProductReservationByProviderRequest($reservation, $identity, [
            'note' => $note,
            'share_note_by_email' => false,
        ])->assertSuccessful()->assertJsonPath('data.accepted_note', $note);

        $this->assertSame($note, $reservation->refresh()->accepted_note);
        $this->assertSame(0, $reservation->provider_messages()->count());
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testApprovalMessageTreatsZeroAsFilledNote(): void
    {
        [
            'reservation' => $reservation,
            'identity' => $identity,
        ] = $this->makeProviderMessageContext();

        $this->apiAcceptProductReservationByProviderRequest($reservation, $identity, [
            'note' => '0',
            'share_note_by_email' => true,
        ])->assertSuccessful();

        $this->assertSame(
            ProviderMessage::TYPE_APPROVE_RESERVATION,
            $reservation->provider_messages()->where('message', '0')->value('type'),
        );
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testRejectedFundProviderCanReadAndExportExistingProviderMessages(): void
    {
        [
            'reservation' => $reservation,
            'fundProvider' => $fundProvider,
            'identity' => $identity,
        ] = $this->makeProviderMessageContext();

        $regularMessage = 'Existing regular message';

        /** @var ProviderMessage $message */
        $message = $reservation->provider_messages()->create([
            'type' => ProviderMessage::TYPE_REGULAR_MESSAGE,
            'message' => $regularMessage,
            'identity_id' => $reservation->voucher->identity_id,
        ]);

        $fundProvider->setState(FundProvider::STATE_REJECTED);

        $this->apiGetProductReservationByProviderRequest($reservation, $identity)
            ->assertSuccessful()
            ->assertJsonPath('data.allow_provider_messages', false);

        $this->apiGetProductReservationProviderMessagesRequest($reservation, $identity)
            ->assertSuccessful()
            ->assertJsonFragment([
                'id' => $message->id,
                'message' => $regularMessage,
            ]);

        $this->apiExportProductReservationProviderMessageRequest($reservation, $message, $identity)
            ->assertSuccessful()
            ->assertHeader('content-type', 'application/pdf');
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderMessageHtmlEscapesPlainTextAndPreservesLineBreaks(): void
    {
        $message = new ProviderMessage([
            'message' => "<img src=\"https://example.invalid/track\">\nSecond line & more",
        ]);
        $html = $message->getMessageHtml();

        $this->assertStringContainsString('&lt;img', $html);
        $this->assertStringNotContainsString('<img', $html);
        $this->assertStringContainsString('<br />', $html);
        $this->assertStringContainsString('Second line &amp; more', $html);
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testProviderMessageEmailEscapesPlainTextAndPreservesLineBreaks(): void
    {
        [
            'reservation' => $reservation,
            'fundProvider' => $fundProvider,
            'identity' => $identity,
        ] = $this->makeProviderMessageContext();

        $fundProvider->update(['allow_provider_messages' => true]);
        $message = "First line\n<img src=\"https://example.invalid/track\"> & second line";
        $startTime = now();

        $this->apiStoreProductReservationProviderMessageRequest(
            $reservation,
            $identity,
            compact('message'),
        )->assertSuccessful();

        $email = $this->findEmailLog(
            $reservation->voucher->identity,
            ProductReservationProviderMessageMail::class,
            $startTime,
        );

        $this->assertStringContainsString('First line<br>', $email->content);
        $this->assertStringContainsString('&lt;img', $email->content);
        $this->assertStringNotContainsString('<img src="https://example.invalid/track">', $email->content);
        $this->assertStringContainsString('&amp; second line', $email->content);
    }

    /**
     * @param bool $requesterHasEmail
     * @throws Throwable
     * @return array{reservation: ProductReservation, fundProvider: FundProvider, identity: Identity}
     */
    private function makeProviderMessageContext(bool $requesterHasEmail = true): array
    {
        $sponsor = $this->makeTestOrganization($this->makeIdentity());
        $fund = $this->makeTestFund($sponsor);
        $product = $this->makeProviderAndProducts($fund, 1)['approved'][0];
        $requester = $this->makeIdentity($requesterHasEmail ? $this->makeUniqueEmail() : null);
        $voucher = $this->makeTestVoucher($fund, $requester);
        $reservation = $this->makeReservation($voucher, $product);
        $fundProvider = $product->organization->fund_providers()->where('fund_id', $fund->id)->firstOrFail();
        $identity = $product->organization->identity;

        $fundProvider->update(['allow_provider_messages' => false]);

        return compact('reservation', 'fundProvider', 'identity');
    }
}
