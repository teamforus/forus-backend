<?php
namespace App\Http\Controllers\Api\Platform\Precheck;

use App\Http\Controllers\Controller;
use App\Support\Microservices\BaseMicroClient;
use App\Support\Microservices\PrecheckMicroClient;
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

    /** GET /api/v1/pre-checks/sessions/{id}/events -> GET /v1/sessions/{id}/events */
    public function events(Request $request, string $id)
    {
        $client = new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);

        $res = $client->streamChat($id, $request->query(), $headers);

        if($res->failed()) {
            return new StreamedResponse(function () use ($res) {
                echo "event: error\n";
                echo 'data: ' . json_encode([
                        'error' => $res->json('detail') ?? $res->json('error' ?? 'Upstream error'),
                        'status' => $res->status(),
                    ], JSON_UNESCAPED_UNICODE) . "\n\n";
                @ob_flush(); flush();
            }, $this->mapStatus($res->status()), [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
            ]);
        }
        return response()->stream(function () use ($res) {
            $body = $res->toPsrResponse()->getBody();
            while(!$body->eof()) {
                echo $body->read(8192);
                @ob_flush(); flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
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
        $setCookie = $res->header('Set-Cookie');
        //TODO: fix cookie pass through
        if ($setCookie) {
            if (is_array($setCookie)) {
                foreach ($setCookie as $cookieLine) {
                    $response->headers->set('Set-Cookie', $cookieLine, false);
                }
            } else {
                $response->headers->set('Set-Cookie', $setCookie, false);
            }
        }

        if(!$isJson && ($ct = $res->header('Content-Type'))) {
            $response->headers->set('Content-Type', $ct, false);
        }

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