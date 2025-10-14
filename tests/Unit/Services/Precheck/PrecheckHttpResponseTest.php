<?php

namespace Tests\Unit\Services\Precheck;

use App\Support\Microservices\Precheck\PrecheckHttpResponse;
use Illuminate\Http\Client\Response as HttpClientResponse;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use GuzzleHttp\Psr7\Response as Psr7Response;



/**
 * @covers \App\Support\Microservices\Precheck\PrecheckHttpResponse
 */
final class PrecheckHttpResponseTest extends TestCase
{
    private function upstreamText(string $body, int $status = 200, array $headers = []): HttpClientResponse
    {
        $psr = new Psr7Response($status, $headers, $body);
        return new HttpClientResponse($psr);
    }

    private function upstreamJson(array $data, int $status, array $headers = []): HttpClientResponse
    {
        $psr = new Psr7Response($status, $headers, json_encode($data, JSON_UNESCAPED_UNICODE));
        return new HttpClientResponse($psr);
    }

    /** @test */
    public function testToResponse() {
        $upstream = $this->upstreamText('plain-ok', 200, ['Content-Type' => 'text/plain']);
        $response = (new PrecheckHttpResponse($upstream))->toResponse();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('text/plain', strtolower($response->headers->get('content-type')));
        $this->assertSame('plain-ok', $response->getContent());
    }

    /** @test */
    public function it_passthroughs_json_success()
    {
        $upstream = $this->upstreamJson(['ok' => true], 200, ['Content-Type' => 'application/json']);
        $resp = (new PrecheckHttpResponse($upstream))->toResponse();

        $this->assertSame(200, $resp->getStatusCode());
        $this->assertSame('application/json', $resp->headers->get('Content-Type'));
        $this->assertJsonStringEqualsJsonString(json_encode(['ok' => true], JSON_UNESCAPED_UNICODE), $resp->getContent());
    }

    /** @test */
    public function it_preserves_problem_json_from_upstream()
    {
        $payload = [
            'type' => 'https://example.com/problem',
            'title' => 'Upstream said no',
            'status' => 422,
            'detail' => 'Validation failed',
        ];
        $upstream = $this->upstreamJson($payload, 422, ['Content-Type' => 'application/problem+json']);
        $resp = (new PrecheckHttpResponse($upstream))->toResponse();

        $this->assertSame(422, $resp->getStatusCode());
        $this->assertSame('application/problem+json', $resp->headers->get('Content-Type'));
        $this->assertJsonStringEqualsJsonString(json_encode($payload, JSON_UNESCAPED_UNICODE), $resp->getContent());
    }

    /** @test */
    public function it_wraps_non_problem_errors_into_problem_json()
    {
        $payload = ['message' => 'Nope'];
        $upstream = $this->upstreamJson($payload, 400, ['Content-Type' => 'application/json', 'X-Request-Id' => 'abc-123']);
        $resp = (new PrecheckHttpResponse($upstream))->toResponse();

        $this->assertSame(400, $resp->getStatusCode());
        $this->assertSame('application/problem+json', $resp->headers->get('Content-Type'));
        $this->assertSame('abc-123', $resp->headers->get('X-Request-Id'));

        $json = json_decode($resp->getContent(), true);
        $this->assertSame('about:blank', $json['type']);
        $this->assertSame(400, $json['status']);
        $this->assertSame('Bad Request', $json['title']); // afgeleid via statusText
        $this->assertSame('Nope', $json['detail']);
        $this->assertArrayHasKey('instance', $json);
    }

    /** @test */
    public function it_maps_5xx_to_502_and_wraps_as_problem_json()
    {
        $upstream = $this->upstreamText('server error', 503, ['Content-Type' => 'text/plain']);
        $resp = (new PrecheckHttpResponse($upstream))->toResponse();

        $this->assertSame(502, $resp->getStatusCode());
        $this->assertSame('application/problem+json', $resp->headers->get('Content-Type'));

        $json = json_decode($resp->getContent(), true);
        $this->assertSame(502, $json['status']);
        $this->assertSame('Bad Gateway', $json['title']);
        $this->assertSame('server error', $json['detail']);
    }

    /** @test */
    public function it_skips_hop_by_hop_headers()
    {
        $upstream = $this->upstreamJson([ 'ok' => true], 200, [
            'content-type' => 'application/json',
            'connection' => 'keep-alive',
            'transfer-encoding' => 'chunked',
            'keep-alive' => 'timeout=5',
        ]);
        $resp = (new PrecheckHttpResponse($upstream))->toResponse();

        $this->assertNull($resp->headers->get('Connection'));
        $this->assertNull($resp->headers->get('Transfer-Encoding'));
        $this->assertNull($resp->headers->get('Keep-Alive'));
    }

}
