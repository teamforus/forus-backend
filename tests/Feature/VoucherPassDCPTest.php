<?php

namespace Tests\Feature;

use App\Modules\DCP\Data\DCPOrderData;
use App\Modules\DCP\Enums\DCPOrderState;
use App\Modules\VoucherPass\Models\VoucherPass;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Tests\Traits\MakesTestDCPOrders;
use Throwable;

class VoucherPassDCPTest extends TestCase
{
    use DatabaseTransactions;
    use MakesTestDCPOrders;

    /**
     * @throws Throwable
     * @return void
     */
    public function testPreparationCreatesPrintingDataForInactiveRequestedPass(): void
    {
        ['organization' => $organization, 'pass' => $pass, 'owner' => $owner, 'recipient' => $recipient] =
            $this->makeDCPFixture();

        $pass->update(['state' => VoucherPass::STATE_DEACTIVATED]);
        $employee = $organization->employees()->where('identity_address', $organization->identity_address)->sole();
        $this->setDCPProfileValues($owner, ['initials' => 'S.P.'], ['employee_id' => $employee->id]);
        $this->setDCPProfileValues($recipient, ['given_name' => 'Sam']);
        $order = $this->prepareDCPOrder($pass);
        $tag = $order->order_rows[0]['tagIds'][0];

        $this->assertSame('Gelrepas', $order->label);
        $this->assertSame('S.P.', $tag['printedFirstName']);
        $this->assertSame('Sam', $order->delivery_first_name);
        $this->assertSame('Residential Road 10', $order->delivery_address1);
        $this->assertSame('1234AB', $order->delivery_postal_code);
        $this->assertSame('DUIVEN', $order->delivery_city);
        $this->assertSame($pass->code, $tag['printedCardNumber']);
        $this->assertSame(['type' => 'pass', 'value' => $pass->code], json_decode($tag['printedQrCode'], true));
        $this->assertSame('deactivated', $pass->refresh()->state);
        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testEmptyPrintLabelIsOmittedFromDcpPrintingData(): void
    {
        ['pass' => $pass] = $this->makeDCPFixture();
        $pass->update(['print_label' => '']);
        $order = $this->prepareDCPOrder($pass);
        $payload = DCPOrderData::fromOrder($order)->toArray($order->order_id);
        $tag = $payload['orderRows'][0]['tagIds'][0];

        $this->assertArrayNotHasKey('printedCardType', $tag);
        $this->assertSame('T.O.', $tag['printedFirstName']);
        $this->assertSame($pass->code, $tag['printedCardNumber']);
        $this->assertSame('Robin', $payload['deliveryAddress']['firstname']);
        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testCompleteMailingAddressOverridesResidentialAddress(): void
    {
        ['pass' => $pass, 'recipient' => $recipient] = $this->makeDCPFixture();

        $this->setDCPProfileValues($recipient, [
            'house_letter' => 'R',
            'house_number_addition' => 'rear',
            'mailing_street' => 'Post Street',
            'mailing_house_number' => '22',
            'mailing_house_letter' => 'M',
            'mailing_house_number_addition' => 'bis',
            'mailing_postal_code' => '9876 cd',
            'mailing_city' => 'Arnhem',
        ]);

        $order = $this->prepareDCPOrder($pass);

        $this->assertSame('de Recipient', $order->delivery_last_name);
        $this->assertSame('Post Street 22 M bis', $order->delivery_address1);
        $this->assertSame('9876CD', $order->delivery_postal_code);
        $this->assertSame('ARNHEM', $order->delivery_city);
        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPartialMailingAddressFailsWithoutResidentialFallback(): void
    {
        ['organization' => $organization, 'pass' => $pass, 'recipient' => $recipient] = $this->makeDCPFixture();
        $this->setDCPProfileValues($recipient, ['mailing_house_letter' => 'A']);

        foreach ([true, false] as $preview) {
            $response = $this
                ->apiPrepareVoucherPassDCPRequest(
                    $organization,
                    ['pass_ids' => [$pass->id]],
                    $organization->identity,
                    preview: $preview,
                )
                ->assertOk()
                ->assertJsonPath('data.0.status', $preview ? 'blocked' : 'failed');

            $this->assertNotEmpty($response->json('data.0.errors'));
            $this->assertFalse($pass->dcp_orders()->exists());
        }

        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testMissingProfileDataFailsOnlyItsPass(): void
    {
        foreach (['owner' => 'initials', 'recipient' => 'street'] as $role => $field) {
            $fixture = $this->makeDCPFixture();
            ['organization' => $organization, 'pass' => $invalid] = $fixture;
            $validOwner = $this->makeVoucherPassParty($organization);
            $validRecipient = $this->makeVoucherPassParty($organization);

            $valid = $this->makeAdditionalDCPPass($invalid, [
                'external_id' => 'valid-pass',
                'owner_identity_id' => $validOwner->id,
                'target_identity_id' => $validRecipient->id,
            ]);

            foreach (['owner' => $validOwner, 'recipient' => $validRecipient] as $key => $identity) {
                $values = $fixture[$key]->profile_records
                    ->mapWithKeys(fn ($record) => [$record->record_type->key => $record->value])
                    ->all();

                $this->setDCPProfileValues($organization->findOrMakeProfile($identity), $values);
            }

            $this->setDCPProfileValues($fixture[$role], [$field => '']);

            $response = $this
                ->apiPrepareVoucherPassDCPRequest($organization, [
                    'pass_ids' => [$invalid->id, $valid->id],
                ], $organization->identity)
                ->assertOk()
                ->assertJsonPath('data.0.status', 'failed')
                ->assertJsonPath('data.1.status', 'created');

            $this->assertNotEmpty($response->json('data.0.errors'));
            $this->assertStringContainsString(
                trans('voucher_pass::dcp.fields')[$role . '.' . $field],
                implode(' ', $response->json('data.0.errors')),
            );

            $this->assertFalse($invalid->dcp_orders()->exists());
            $this->assertSame($valid->dcp_orders()->sole()->id, $response->json('data.1.order_id'));
        }

        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPreparationIdsAreExplicitAndOrganizationScoped(): void
    {
        ['organization' => $organization, 'pass' => $pass] = $this->makeDCPFixture();
        ['pass' => $foreign] = $this->makeDCPFixture();
        $excluded = $this->makeAdditionalDCPPass($pass);
        $notRequested = $this->makeAdditionalDCPPass($pass, [
            'external_id' => 'digital-only',
            'print_requested' => false,
        ]);

        $this
            ->apiGetVoucherPassDCPIdsRequest($organization, $organization->identity)
            ->assertOk()
            ->assertExactJson(['data' => [$pass->id, $excluded->id]]);

        foreach ([false, true] as $preview) {
            $this
                ->apiPrepareVoucherPassDCPRequest($organization, ['pass_ids' => []], $organization->identity, $preview)
                ->assertOk()
                ->assertExactJson(['data' => []]);

            foreach ([[], ['pass_ids' => range(1, 101)], ['pass_ids' => 'invalid']] as $data) {
                $this
                    ->apiPrepareVoucherPassDCPRequest($organization, $data, $organization->identity, $preview)
                    ->assertUnprocessable()
                    ->assertJsonValidationErrors('pass_ids');
            }
        }

        $this
            ->apiPrepareVoucherPassDCPRequest($organization, [
                'pass_ids' => [$pass->id, $foreign->id, $notRequested->id],
            ], $organization->identity)
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.id', $pass->id)
            ->assertJsonPath('data.0.status', 'created')
            ->assertJsonPath('data.1.id', $notRequested->id)
            ->assertJsonPath('data.1.status', 'skipped');

        $this->assertFalse($foreign->dcp_orders()->exists());
        $this->assertFalse($excluded->dcp_orders()->exists());
        $this->assertFalse($notRequested->dcp_orders()->exists());
        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testDraftResyncUpdatesDataWithoutChangingOrderIdentity(): void
    {
        $this->freezeTime();
        ['organization' => $organization, 'pass' => $pass, 'recipient' => $recipient] = $this->makeDCPFixture();
        $order = $this->prepareDCPOrder($pass);
        $before = $order->getAttributes();
        $logs = $order->logs()->count();
        $this->assertSame(1, $logs);
        $this->travel(1)->minute();
        $this->setDCPProfileValues($recipient, ['city' => 'Amsterdam']);

        $preview = $this
            ->apiPrepareVoucherPassDCPRequest(
                $organization,
                ['pass_ids' => [$pass->id]],
                $organization->identity,
                preview: true,
            )
            ->assertOk()
            ->assertJsonPath('data.0.status', 'would_update')
            ->assertJsonPath('data.0.order_id', $order->id);

        $this->assertSame([[
            'field' => 'delivery_city',
            'previous_value' => 'DUIVEN',
            'value' => 'AMSTERDAM',
            'action' => 'update',
        ]], $preview->json('data.0.changes'));

        $this->assertSame($before, $order->fresh()->getAttributes());
        $this->assertSame($logs, $order->logs()->count());

        $this
            ->apiPrepareVoucherPassDCPRequest($organization, ['pass_ids' => [$pass->id]], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'updated')
            ->assertJsonPath('data.0.order_id', $order->id)
            ->assertJsonPath('data.0.code', $pass->code);

        $order->refresh();
        $this->assertSame($before['order_id'], $order->order_id);
        $this->assertSame('AMSTERDAM', $order->delivery_city);
        $this->assertSame($logs + 1, $order->logs()->count());
        $this->assertNotSame($before['updated_at'], $order->getRawOriginal('updated_at'));
        $updated = $order->getAttributes();
        $this->travel(1)->minute();

        $this
            ->apiPrepareVoucherPassDCPRequest($organization, ['pass_ids' => [$pass->id]], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'unchanged')
            ->assertJsonPath('data.0.changes', []);

        $this->assertSame($updated, $order->fresh()->getAttributes());
        $this->assertSame($logs + 1, $order->logs()->count());
        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPreparationDoesNotChangeFrozenOrders(): void
    {
        ['organization' => $organization, 'pass' => $pass, 'recipient' => $recipient] = $this->makeDCPFixture();
        $order = $this->prepareDCPOrder($pass);
        $this->setDCPProfileValues($recipient, ['city' => 'Amsterdam']);

        foreach ([DCPOrderState::APPROVED, DCPOrderState::UNKNOWN, DCPOrderState::ACCEPTED] as $state) {
            $order->update(['state' => $state]);
            $before = $order->getAttributes();

            foreach ([false, true] as $preview) {
                $this
                    ->apiPrepareVoucherPassDCPRequest(
                        $organization,
                        ['pass_ids' => [$pass->id]],
                        $organization->identity,
                        $preview,
                    )
                    ->assertOk()
                    ->assertJsonPath('data.0.status', 'skipped')
                    ->assertJsonPath('data.0.order_id', $order->id);

                $this->assertSame($before, $order->fresh()->getAttributes());
            }
        }

        Http::assertNothingSent();
    }

    /**
     * @throws Throwable
     * @return void
     */
    public function testPreparationRejectsMovingDraftToChangedConnection(): void
    {
        ['organization' => $organization, 'pass' => $pass] = $this->makeDCPFixture();
        $order = $this->prepareDCPOrder($pass);
        $before = $order->getAttributes();
        $replacement = $this->makeDCPConnection($organization);
        $organization->forceFill(['dcp_connection_id' => $replacement->id])->save();

        $this
            ->apiPrepareVoucherPassDCPRequest($organization, ['pass_ids' => [$pass->id]], $organization->identity)
            ->assertOk()
            ->assertJsonPath('data.0.status', 'failed');

        $this->assertSame($before, $order->fresh()->getAttributes());
        $newPass = $this->makeAdditionalDCPPass($pass);
        $organization->forceFill(['dcp_connection_id' => null])->save();

        $this
            ->apiPrepareVoucherPassDCPRequest($organization, [
                'pass_ids' => [$pass->id, $newPass->id],
            ], $organization->identity)
            ->assertForbidden();

        $this->assertSame($before, $order->fresh()->getAttributes());
        $this->assertSame(1, $pass->dcp_orders()->count());
        $this->assertFalse($newPass->dcp_orders()->exists());
        Http::assertNothingSent();
    }
}
