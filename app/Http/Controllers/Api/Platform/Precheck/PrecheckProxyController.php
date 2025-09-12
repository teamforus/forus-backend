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
                $response->headers->set('Location', "/api/v1/pre-checks/sessions/{$id}");

                $now = time();
                $exp = $now + 900; // 15 min

                $jwtPayload = [
                    'sub' => $id,                  // session id
                    'org' => $headers['X-Client-Key'], // organization
                    'iat' => $now,
                    'exp' => $exp,
                ];

                $privateKey = file_get_contents(config('jwt.private'));
                $token = JWT::encode($jwtPayload, $privateKey, 'RS256');
                $payload['stream_token'] = $token;
                $response = response()->json($payload, 201, [], JSON_UNESCAPED_UNICODE);
            }
        }
        return $response;
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
        $client = new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);

        if ($request->headers->has('Idempotency-Key')) {
            $headers['Idempotency-Key'] = $request->headers->get('Idempotency-Key');
        }

        $res = $client->sendAnswer($id, $request->all(), $headers);

        return $this->toLaravelResponse($res);
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
        $isJson = $this->looksLikeJson($res->header('Content-Type'));
        $payload = $isJson ? ($res->json() ?? []) : $res->body();

        $status = $res->status();
        $status = $this->mapStatus($status);

        $response = $isJson
            ? response()->json($payload, $status, [], JSON_UNESCAPED_UNICODE)
            : response($payload, $status);

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
            if($key === 'x-request-id') {
                $response->headers->set('X-Request-Id',  implode(', ', (array) $values));
            }
            foreach ((array) $values as $value) {
                $response->headers->set($key, implode(', ', (array) $values));
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
}