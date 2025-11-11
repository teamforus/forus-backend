<?php
namespace App\Http\Controllers\Api\Platform\Precheck;

use App\Http\Controllers\Controller;
use App\Http\Responses\NoContentResponse;
use App\Support\Microservices\BaseMicroClient;
use App\Support\Microservices\Precheck\PrecheckHttpResponse;
use App\Support\Microservices\Precheck\PrecheckMicroClient;
use Firebase\JWT\JWT;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class PrecheckProxyController extends Controller
{
//    TODO: define response type and move methods there
    /**
     * @param Request $request
     * @return JsonResponse|ResponseAlias
     * @throws ConnectionException
     */
    public function sessions(Request $request)
    {
        $client =  new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);
        $res = $client->createSession($request->all(), $headers);
        $response = (new PrecheckHttpResponse($res))->toResponse();
        if ($res->successful()) {
            $payload = $res->json() ?? [];
            $id = data_get($payload, 'session_id');
            $token = $this->mintStreamToken($id, $headers['X-Client-Key'] ?? '');
            $payload['stream_token'] = $token;
            $response = new JsonResponse(data: $payload, status: 201, headers: [
                'X-Request-Id' => $res->header('x-request-id'),
                'Location' => "/api/v1/pre-checks/sessions/$id",
            ], options: JSON_UNESCAPED_UNICODE);
        }
        return $response;
    }

    /**
     * @param Request $request
     * @param string $id
     * @return JsonResponse
     */
    public function stream_token(Request $request, string $id)
    {
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);
        $org = $request->header('x-client-key') ?? $headers['X-Client-Key'];
        $payload['stream_token'] = $this->mintStreamToken($id, $org);
        return new JsonResponse(data: $payload, status: 200, headers: [], options: JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param Request $request
     * @param string $id
     * @return NoContentResponse|object|Response
     * @throws ConnectionException
     */
    public function end(Request $request, string $id)
    {
        $client = new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);

        $res = $client->endSession($id, $headers);

        if ($res->successful()) {
            // Uniformly return 204 No Content on success
            return new NoContentResponse();
        }

        return (new PrecheckHttpResponse($res))->toResponse();
    }

    /**
     * @param Request $request
     * @param string $id
     * @return ResponseAlias
     * @throws ConnectionException
     */
    public function advice(Request $request, string $id)
    {
        $client = new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);

        $res = $client->advice($id, $headers);

        return (new PrecheckHttpResponse($res))->toResponse();
    }

    /**
     * @param Request $request
     * @param string $id
     * @return ResponseAlias
     * @throws ConnectionException
     */
    public function answer(Request $request, string $id)
    {
        $client = new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);

        if ($request->headers->has('Idempotency-Key')) {
            $headers['Idempotency-Key'] = $request->headers->get('Idempotency-Key');
        }

        $res = $client->sendAnswer($id, $request->all(), $headers);

        $response = (new PrecheckHttpResponse($res))->toResponse();
        $response->headers->set('X-Request-Id', $res->header('x-request-id'));
        return $response;
    }

    /**
     * @param Request $request
     * @param string $id
     * @return ResponseAlias
     * @throws ConnectionException
     */
    public function messages(Request $request, string $id)
    {
        $client = new PrecheckMicroClient();
        $headers = BaseMicroClient::forwardHeadersFromRequest($request);
        $res = $client->history($id, $headers);
        return (new PrecheckHttpResponse($res))->toResponse();
    }

    /**
     * Mint a JWT token for streaming access
     *
     * @param string $sessionId
     * @param string $orgKey
     * @param int|null $ttl
     * @return string
     */
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