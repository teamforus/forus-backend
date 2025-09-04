<?php

namespace App\Support\Microservices;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class BaseMicroClient
{
    protected string $serviceKey;

    public function __construct(string $serviceKey)
    {
        $this->serviceKey = $serviceKey;
    }

    /** Factory: BaseMicroClient::for('precheck_micro') */
    public static function for(string $serviceKey): self
    {
        return new self($serviceKey);
    }

    /** Returns cfg for service */
    protected function cfg(): array
    {
        $cfg = config("services.{$this->serviceKey}");
        if (!$cfg || empty($cfg['base_url'])) {
            throw new \RuntimeException("Missing config for service [{$this->serviceKey}]");
        }
        return $cfg;
    }

    protected function base(array $extraHeaders = []): PendingRequest
    {
        $cfg = $this->cfg();

        $req = Http::baseUrl($cfg['base_url'])
            ->timeout((int) ($cfg['timeout'] ?? 15))
            ->retry((int) ($cfg['retries'] ?? 0), 250)
            ->acceptJson();

        if (!empty($cfg['token'])) {
            $req = $req->withToken($cfg['token']);
        }

        if ($extraHeaders) {
            $req = $req->withHeaders($extraHeaders);
        }

        // Forward tracing/correlation
        $incoming = request();
        if ($incoming instanceof Request) {
            $traceHeaders = array_filter([
                'X-Request-Id'    => $incoming->header('X-Request-Id'),
                'traceparent'     => $incoming->header('traceparent'),
                'tracestate'      => $incoming->header('tracestate'),
                'X-Forwarded-For' => $incoming->ip(),
            ], fn($v) => !is_null($v));
            if ($traceHeaders) {
                $req = $req->withHeaders($traceHeaders);
            }
        }

        return $req;
    }

    public function json(array $extraHeaders = []): PendingRequest
    {
        return $this->base($extraHeaders)->asJson();
    }

    public function stream(array $extraHeaders = []): PendingRequest
    {
        return $this->base($extraHeaders)->withOptions(['stream' => true]);
    }

    public function multipart(array $extraHeaders = []): PendingRequest
    {
        return $this->base($extraHeaders)->asMultipart();
    }

    public static function forwardHeadersFromRequest(?Request $request = null): array
    {
        $request ??= request();
        return $request->attributes->get('forward_headers', []) ?? [];
    }
}
