<?php

namespace Tests\Unit;

use App\Services\DCPService\Responses\DCPResponse;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Tests\TestCase;

class DCPResponseTest extends TestCase
{
    /**
     * @return void
     */
    public function testCreatedOrder(): void
    {
        $response = $this->makeResponse(201, [
            'orderIdDCP' => 'DCP-123',
            'statusId' => '100',
            'statusMessage' => 'Web API accepted',
        ]);

        $this->assertTrue($response->success());
        $this->assertFalse($response->rejected());
        $this->assertFalse($response->isDuplicateOrder());
        $this->assertSame(201, $response->httpStatus());
        $this->assertSame('DCP-123', $response->orderIdDcp());
        $this->assertSame(100, $response->statusId());
        $this->assertSame('Web API accepted', $response->statusMessage());
        $this->assertSame([], $response->validationErrors());
        $this->assertNull($response->shippingDate());
    }

    /**
     * @return void
     */
    public function testOrderStatus(): void
    {
        $response = $this->makeResponse(200, [
            'statusId' => 500,
            'messageStatus' => 'Shipped',
            'shippingDate' => '2026-11-05T10:00:00',
        ]);

        $this->assertSame(500, $response->statusId());
        $this->assertSame('Shipped', $response->statusMessage());
        $this->assertSame('2026-11-05', $response->shippingDate()?->format('Y-m-d'));

        $this->assertNull($this->makeResponse(200, ['statusId' => 'unknown'])->statusId());
    }

    /**
     * @return void
     */
    public function testValidationErrors(): void
    {
        $errors = [
            'DeliveryAddress.Zipcode: The zipcode is invalid.',
            'OrderRows[0].TagIds[0].printedDateOfBirth: The field is required.',
        ];

        $response = $this->makeResponse(422, ['statusMessage' => 'Invalid order', 'validationErrors' => $errors]);

        $this->assertFalse($response->success());
        $this->assertTrue($response->rejected());
        $this->assertFalse($response->isDuplicateOrder());
        $this->assertSame($errors, $response->validationErrors());
        $this->assertSame('Invalid order ' . implode(' ', $errors), $response->statusMessage());

        $this->assertTrue($this->makeResponse(400, ['validationErrors' => $errors])->rejected());
    }

    /**
     * @return void
     */
    public function testDuplicateOrder(): void
    {
        $this->assertTrue($this->makeResponse(422, [
            'validationErrors' => ['ReferenceClient: Order with this ReferenceClient Already Exists.'],
        ])->isDuplicateOrder());

        // only the rejected responses about the client reference are duplicates
        $this->assertFalse($this->makeResponse(422, [
            'validationErrors' => ['ReferenceClient: The field is required.'],
        ])->isDuplicateOrder());

        $this->assertFalse($this->makeResponse(500, [
            'validationErrors' => ['ReferenceClient: Order with this ReferenceClient already exists.'],
        ])->isDuplicateOrder());
    }

    /**
     * @return void
     */
    public function testConnectionError(): void
    {
        $response = new DCPResponse(null, new ConnectionException('Connection timed out'));

        $this->assertFalse($response->success());
        $this->assertFalse($response->rejected());
        $this->assertNull($response->httpStatus());
        $this->assertNull($response->body());
        $this->assertNull($response->statusId());
        $this->assertSame('Connection timed out', $response->statusMessage());
    }

    /**
     * @param int $status
     * @param array $body
     * @return DCPResponse
     */
    protected function makeResponse(int $status, array $body): DCPResponse
    {
        return new DCPResponse(new Response(new PsrResponse($status, [
            'Content-Type' => 'application/json',
        ], json_encode($body))));
    }
}
