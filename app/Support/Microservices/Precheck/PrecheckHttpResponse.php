<?php

namespace App\Support\Microservices\Precheck;
use Illuminate\Http\Client\Response as HttpClientResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class PrecheckHttpResponse
{
    private ?HttpClientResponse $upstream;

    public function __construct(
        ?HttpClientResponse $upstream = null,
    ){
        $this->upstream = $upstream;

    }
    public function toResponse(): SymfonyResponse
    {

        $contentType = $this->upstream->header('Content-Type');
        $isJson = $this->looksLikeJson($contentType);
        $payload = $isJson ? ($this->upstream->json() ?? []) : $this->upstream->body();

        $isProblem = $contentType && str_contains(strtolower($contentType), 'application/problem+json');

        $status = $this->upstream->status();
        $status = $this->mapStatus($status);

        $shouldWrapAsProblem = $status >= 400 && !$isProblem;
        if ($shouldWrapAsProblem) {
            $detail = is_array($payload)
                ? ($payload['detail'] ?? $payload['message'] ?? null)
                : (string) $payload;

            $title = is_array($payload)
                ? ($payload['title'] ?? null)
                : null;

            $type = is_array($payload)
                ? ($payload['type'] ?? null)
                : null;

            $problem = [
                'type'     => $type ?: 'about:blank',
                'title'    => $title ?: ($this->statusText($status) ?: 'Error'),
                'status'   => $status,
                'instance' => request()->getPathInfo(),
            ];

            if ($detail !== null && $detail !== '') {
                $problem['detail'] = is_string($detail) ? $detail : json_encode($detail, JSON_UNESCAPED_UNICODE);
            }

            if (is_array($payload) && isset($payload['errors'])) {
                $problem['errors'] = $payload['errors'];
            }

            $response = response()->json($problem, $status, [], JSON_UNESCAPED_UNICODE);
            $response->headers->set('Content-Type', 'application/problem+json');
        } else {
            $response = $isJson
                ? response()->json($payload, $status, [], JSON_UNESCAPED_UNICODE)
                : response($payload, $status);
            if ($isProblem) {
                $response->headers->set('Content-Type', 'application/problem+json');
            }
        }

        $skip = [
            'transfer-encoding',
            'content-encoding',
            'connection',
            'keep-alive',
            'proxy-authenticate',
            'proxy-authorization',
            'te',
            'trailer',
            'upgrade',
        ];

        foreach ($this->upstream->headers() as $key => $values) {
            if (in_array($key, $skip, true)) {
                continue;
            }
            if ($shouldWrapAsProblem && strtolower($key) === 'content-type') {
                continue;
            }
            if ($key === 'x-request-id') {
                $response->headers->set('X-Request-Id', implode(', ', (array) $values));
                continue;
            }
            foreach ((array) $values as $value) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }


    private function mapStatus(int $status): int
    {
        if ($status >= 500) {
            return SymfonyResponse::HTTP_BAD_GATEWAY; // 502
        }
        return $status;
    }

    private function looksLikeJson(?string $contentType): bool
    {
        if (!$contentType) return true;
        return str_contains(strtolower($contentType), 'application/json')
            || str_contains(strtolower($contentType), '+json');

    }

    private function statusText(int $status): ?string
    {
        return SymfonyResponse::$statusTexts[$status] ?? null;
    }
}