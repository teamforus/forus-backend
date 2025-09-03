<?php
namespace Api\Platform\Precheck\PrecheckProxy;

use App\Http\Controllers\Controller;
use App\Support\Microservices\BaseMicroClient;
use App\Support\Microservices\PrecheckMicroClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrecheckProxyController extends Controller
{
    /** GET /api/v1/pre-checks/session -> GET /start_session */
    public function start(Request $request)
    {
        $client =  new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);
        $res = $client->startSession($request->all(), $headers);
        return $this->toLaravelResponse($res);
    }

    /** POST /api/v1/pre-checks/end -> POST /end_session */
    public function end(Request $request)
    {
        $client = new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);

        $res = $client->endSession($request->all(), $headers);

        return $this->toLaravelResponse($res);
    }

    /** POST /api/v1/pre-checks/advice -> POST /advice */
    public function advice(Request $request)
    {
        $client = new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);

        $res = $client->advice($request->all(), $headers);

        return $this->toLaravelResponse($res);
    }

    /** GET /api/v1/pre-checks/chat/stream -> GET /chat/stream */
    public function stream(Request $request)
    {
        $client = new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);
        $res = $client->streamChat($request->query(), $headers);

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

    /** POST /api/v1/pre-checks/chat/answer -> POST /chat/answer */
    public function answer(Request $request)
    {
        $client = new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);

        $res = $client->sendAnswer($request->all(), $headers);

        return $this->toLaravelResponse($res);
    }

    /** POST /api/v1/pre-checks/chat/history -> POST /chat/history */
    public function history(Request $request)
    {
        $client = new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);

        $res = $client->history($request->all(), $headers);

        return $this->toLaravelResponse($res);
    }

    public function toLaravelResponse($res)
    {
        $isJson = $this->looksLikeJson($res->headers->get('Content-Type'));
        $payload = $isJson ? ($res->json() ?? []) : $res->body();

        $status = $res->status();
        $status = $this->mapStatus($status);

        $response = $isJson
            ? response()->json($payload, $status, [], JSON_UNESCAPED_UNICODE)
            : response($payload, $status);

        // Set-Cookie headers doorgeven (kan array of string zijn)
        $setCookie = $res->headers->get('Set-Cookie');
        if ($setCookie) {
            if (is_array($setCookie)) {
                foreach ($setCookie as $cookieLine) {
                    $response->headers->set('Set-Cookie', $cookieLine, false);
                }
            } else {
                $response->headers->set('Set-Cookie', $setCookie, false);
            }
        }

        if(!$isJson && ($ct = $res->headers->get('Content-Type'))) {
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

    public function looksLikeJson(string $contentType)
    {
        if (!$contentType) return true;
        return str_contains(strtolower($contentType), 'application/json')
            || str_contains(strtolower($contentType), '+json');

    }
}