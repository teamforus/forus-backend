<?php

namespace Tests\Feature;

use App\Modules\DCP\Data\DCPDeliveryAddress;
use App\Modules\DCP\Data\DCPOrderData;
use App\Modules\DCP\Data\GelrepasCardData;
use App\Modules\DCP\DCPApi;
use App\Modules\DCP\Enums\DCPResponseError;
use App\Modules\DCP\Enums\DCPSubmissionOutcome;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use JsonException;
use Tests\TestCase;

class DCPApiTest extends TestCase
{
    /**
     * @throws JsonException
     * @return void
     */
    public function testCreateOrderAcceptsMatchingCreatedResponse(): void
    {
        $api = $this->makeDCPApi();

        Http::fake(['https://dcp.test/api/Orders' => Http::response([
            'orderId' => 'local-1',
            'orderIdDCP' => 'remote-1',
            'statusId' => 100,
            'statusMessage' => 'Accepted',
            'correlationId' => 'correlation-1',
        ], 201)]);

        $result = $api->createOrder('local-1', $this->makeDCPOrderData());

        $this->assertSame(DCPSubmissionOutcome::ACCEPTED, $result->outcome);
        $this->assertNull($result->error);
        $this->assertSame(201, $result->response->httpStatus);
        $this->assertSame('local-1', $result->response->orderId);
        $this->assertSame('remote-1', $result->response->remoteId);
        $this->assertSame(100, $result->response->statusId);
        $this->assertSame('Accepted', $result->response->message);
        $this->assertSame('correlation-1', $result->response->correlationId);

        Http::assertSent(function (Request $request): bool {
            $this->assertSame('https://dcp.test/api/Orders', $request->url());
            $this->assertSame('POST', $request->method());
            $this->assertTrue($request->hasHeader(
                'Authorization',
                'Basic ' . base64_encode('fixture-user:fixture-pass'),
            ));
            $this->assertTrue($request->hasHeader('Accept', 'application/json'));

            $this->assertSame([
                'orderId' => 'local-1',
                'label' => 'Gelrepas',
                'deliveryAddress' => [
                    'firstname' => 'Robin',
                    'lastname' => 'de Recipient',
                    'fullname' => 'Robin de Recipient',
                    'address1' => 'Mail Street 10',
                    'zipcode' => '1234AB',
                    'city' => 'ARNHEM',
                    'country' => 'NETHERLANDS',
                ],
                'orderRows' => [[
                    'key' => 'OrderRow_001',
                    'item' => 'card',
                    'tagIds' => [[
                        'key' => 'tag_001',
                        'printedCardType' => 'Family budget',
                        'printedFirstName' => 'T.O.',
                        'printedLastName' => 'van Owner',
                        'printedDateOfBirth' => '12-04-1990',
                        'printedCityCode' => '2020',
                        'printedQrCode' => '{"type":"pass","value":"AAAA-BBBB-CCCC-DDDD"}',
                        'printedCardNumber' => 'AAAA-BBBB-CCCC-DDDD',
                    ]],
                ]],
            ], $request->data());

            return true;
        });

        Http::assertSentCount(1);
    }

    /**
     * @throws JsonException
     * @return void
     */
    public function testCreateOrderRecognizesConfirmedRejection(): void
    {
        $api = $this->makeDCPApi();
        $responses = Http::sequence();
        Http::fake(['https://dcp.test/api/Orders' => $responses]);

        foreach ([401, 403, 400, 422] as $index => $status) {
            $body = in_array($status, [400, 422]) ? ['validationErrors' => ['zipcode' => ['Invalid postcode']]] : [];
            $responses->push($body, $status);
            $result = $api->createOrder('local-1', $this->makeDCPOrderData());

            $this->assertSame(DCPSubmissionOutcome::REJECTED, $result->outcome);
            $this->assertSame(DCPResponseError::REJECTED, $result->error);
            $this->assertSame($status, $result->response->httpStatus);
            $this->assertNull($result->response->remoteId);
            $this->assertSame($body === [] ? [] : ['zipcode: Invalid postcode'], $result->response->errors);
            Http::assertSentCount($index + 1);
        }
    }

    /**
     * @throws JsonException
     * @return void
     */
    public function testCreateOrderTreatsAmbiguousResponsesAsUnknown(): void
    {
        $api = $this->makeDCPApi();
        $responses = Http::sequence();
        Http::fake(['https://dcp.test/api/Orders' => $responses]);
        $requests = 0;

        $variants = [
            'conflict' => [409, [], DCPResponseError::DUPLICATE],
            'duplicate message' => [422, ['statusMessage' => 'Order already registered'], DCPResponseError::DUPLICATE],
            'different reference' => [201, ['orderId' => 'another-order'], DCPResponseError::REFERENCE_MISMATCH],
            'missing remote reference' => [201, ['orderId' => 'local-1'], DCPResponseError::INVALID_RESPONSE],
            'malformed success' => [201, 'not-json', DCPResponseError::INVALID_RESPONSE],
            'server failure' => [500, [], DCPResponseError::HTTP],
        ];

        foreach ($variants as $name => [$status, $body, $error]) {
            $responses->push($body, $status);
            $result = $api->createOrder('local-1', $this->makeDCPOrderData());

            $this->assertSame(DCPSubmissionOutcome::UNKNOWN, $result->outcome, $name);
            $this->assertSame($error, $result->error, $name);
            Http::assertSentCount(++$requests);
        }

        $responses->pushFailedConnection('Injected connection timeout.');
        $result = $api->createOrder('local-1', $this->makeDCPOrderData());

        $this->assertSame(DCPSubmissionOutcome::UNKNOWN, $result->outcome);
        $this->assertSame(DCPResponseError::TRANSPORT, $result->error);
        $this->assertTrue($responses->isEmpty());
    }

    /**
     * @return void
     */
    public function testStatusResponseRequiresMatchingReferencesAndValidStatus(): void
    {
        $api = $this->makeDCPApi();
        Config::set('app.timezone', 'UTC');

        $body = [
            'orderId' => 'local-1',
            'orderIdDCP' => 'remote/1',
            'statusId' => 500,
            'shippingDate' => '2026-11-13T10:00:00Z',
        ];

        $responses = Http::sequence()->push($body);
        Http::fake(['https://dcp.test/api/Orders/remote%2F1' => $responses]);
        $requests = 1;
        $result = $api->getOrder('local-1', 'remote/1');

        $this->assertNull($result->error);
        $this->assertSame(500, $result->response->statusId);
        $this->assertSame('2026-11-13 10:00:00', $result->response->shippedAt?->format('Y-m-d H:i:s'));

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET' &&
            $request->url() === 'https://dcp.test/api/Orders/remote%2F1' &&
            $request->hasHeader('Authorization', 'Basic ' . base64_encode('fixture-user:fixture-pass')));

        $variants = [
            'different order' => [200, [...$body, 'orderId' => 'other'], DCPResponseError::REFERENCE_MISMATCH],
            'different remote id' => [200, [...$body, 'orderIdDCP' => 'other'], DCPResponseError::REFERENCE_MISMATCH],
            'missing status' => [200, [...$body, 'statusId' => null], DCPResponseError::INVALID_RESPONSE],
            'invalid status' => [200, [...$body, 'statusId' => 'invalid'], DCPResponseError::INVALID_RESPONSE],
            'not found status' => [200, [...$body, 'statusId' => -1], DCPResponseError::NOT_FOUND],
            'not found http' => [404, [], DCPResponseError::NOT_FOUND],
        ];

        foreach ($variants as $name => [$status, $response, $error]) {
            $responses->push($response, $status);

            $this->assertSame($error, $api->getOrder('local-1', 'remote/1')->error, $name);
            Http::assertSentCount(++$requests);
        }

        $responses->pushFailedConnection('Injected status timeout.');
        $this->assertSame(DCPResponseError::TRANSPORT, $api->getOrder('local-1', 'remote/1')->error);
        $this->assertTrue($responses->isEmpty());
    }

    /**
     * @return DCPApi
     */
    private function makeDCPApi(): DCPApi
    {
        Http::preventStrayRequests();

        return new DCPApi('https://dcp.test', 'fixture-user', 'fixture-pass');
    }

    /**
     * @throws JsonException
     * @return DCPOrderData
     */
    private function makeDCPOrderData(): DCPOrderData
    {
        return new DCPOrderData(
            new DCPDeliveryAddress('Robin', 'de Recipient', 'Mail Street 10', '1234AB', 'ARNHEM'),
            new GelrepasCardData(
                'Family budget',
                'T.O.',
                'van Owner',
                CarbonImmutable::parse('1990-04-12'),
                '2020',
                'AAAA-BBBB-CCCC-DDDD',
            ),
        );
    }
}
