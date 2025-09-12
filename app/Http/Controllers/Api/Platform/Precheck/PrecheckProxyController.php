<?php
namespace App\Http\Controllers\Api\Platform\Precheck;

use App\Http\Controllers\Controller;
use App\Support\Microservices\BaseMicroClient;
use App\Support\Microservices\PrecheckMicroClient;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrecheckProxyController extends Controller
{
    /** POST /api/v1/pre-checks/session -> POST /v1/sessions/ */
    public function sessions(Request $request)
    {
        $client =  new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);
        $res = $client->createSession($request->all(), $headers);
        $response = $this->toLaravelResponse($res);
        if ($res->successful()) {
            // Try common shapes for id
            $payload = $res->json() ?? [];
            $id = data_get($payload, 'session_id') ?? data_get($payload, 'data.session_id') ?? null;

            // Prefer 201 for a created session
            $response->setStatusCode(201);

            // Set Location if we have the id
            if ($id) {
                $org = $headers['X-Client-Key'] ?? $request->header('x-client-key');
                $payload['stream_token'] = $this->mintStreamToken($id, $org);

                $response->setContent(json_encode($payload, JSON_UNESCAPED_UNICODE));

                $response->headers->set('Location', "/api/v1/pre-checks/sessions/{$id}");
                $response->headers->set('X-Request-Id', $res->header('x-request-id'));
            }
        }
        return $response;
    }

    /** GET /api/v1/pre-checks/sessions/{id}/stream-token -> mint stream token */
    public function stream_token(Request $request, string $id)
    {
        $org = $headers['X-Client-Key'] ?? $request->header('x-client-key');
        $payload['stream_token'] = $this->mintStreamToken($id, $org);
        return response()->json($payload, 201, [], JSON_UNESCAPED_UNICODE);
    }

    /** DELETE /api/v1/pre-checks/sessions/{id} -> DELETE /v1/sessions/{id} */
    public function end(Request $request, string $id)
    {
        $client = new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);

        $res = $client->endSession($id, $headers);

        if ($res->successful()) {
            // Uniformly return 204 No Content on success
            return response()->noContent();
        }

        return $this->toLaravelResponse($res);
    }

    /** GET /api/v1/pre-checks/sessions/{id}/advice -> GET /v1/sessions/{id}/advice */
    public function advice(Request $request, string $id)
    {
        $client = new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);

        $res = $client->advice($id, $headers);

        return $this->toLaravelResponse($res);
    }

    /** POST /api/v1/pre-checks/sessions/{id}/messages -> POST /v1/sessions/{id}/messages */
    public function answer(Request $request, string $id)
    {
        //todo: new jwt token
        $client = new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);

        if ($request->headers->has('Idempotency-Key')) {
            $headers['Idempotency-Key'] = $request->headers->get('Idempotency-Key');
        }

        $res = $client->sendAnswer($id, $request->all(), $headers);

        $response = $this->toLaravelResponse($res);
        $response->headers->set('X-Request-Id', $res->header('x-request-id'));
        return $response;
    }

    /** GET /api/v1/pre-checks/sessions/{id}/messages -> GET /v1/sessions/{id}/messages */
    public function messages(Request $request, string $id)
    {
        $client = new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);
        $res = $client->history($id, $headers);
        return $this->toLaravelResponse($res);
    }

    public function toLaravelResponse($res)
    {
        $contentType = $res->header('Content-Type');
        $isJson = $this->looksLikeJson($contentType);
        $payload = $isJson ? ($res->json() ?? []) : $res->body();

        $isProblem = $contentType && str_contains(strtolower($contentType), 'application/problem+json');

        $status = $res->status();
        $status = $this->mapStatus($status);

        $shouldWrapAsProblem = $status >= 400 && !$isProblem;

        if ($shouldWrapAsProblem) {
            // Derive title/detail from upstream payload or fallbacks
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
        }

        // headers die je nooit wilt doorgeven
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

        foreach ($res->headers() as $key => $values) {
            if (in_array($key, $skip, true)) {
                continue;
            }
            if ($shouldWrapAsProblem && $key === 'content-type') {
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
        \Log::info("headers: " . json_encode($response->headers->all()));

        return $response;
    }

    private function mapStatus(int $status): int
    {
        if ($status >= 500) {
            return ResponseAlias::HTTP_BAD_GATEWAY; // 502
        }
        return $status;
    }

    public function looksLikeJson(?string $contentType)
    {
        if (!$contentType) return true;
        return str_contains(strtolower($contentType), 'application/json')
            || str_contains(strtolower($contentType), '+json');

    }

    private function statusText(int $status): ?string
    {
        return \Symfony\Component\HttpFoundation\Response::$statusTexts[$status] ?? null;
    }

    private function mintStreamToken(string $sessionId, string $orgKey, ?int $ttl = null): string
    {
        $now = time();
        $ttl ??= (int) config('services.precheck_micro.stream_token_ttl', 900);

        $payload = [
            'sub' => $sessionId,    // session id
            'org' => $orgKey,       // organization
            'iat' => $now,
            'exp' => $now + $ttl,
        ];

        $privateKey = file_get_contents(config('jwt.private'));
        return JWT::encode($payload, $privateKey, 'RS256');
    }
}