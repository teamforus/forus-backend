<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use PhilKra\Agent;
use PhilKra\Exception\Timer\NotStartedException;
use PhilKra\Exception\Transaction\DuplicateTransactionNameException;
use PhilKra\Helper\Timer;
use Closure;

class RecordApmTransactions
{
    /**
     * @var Agent
     */
    protected $agent;
    /**
     * @var Timer
     */
    private $timer;

    /**
     * RecordTransaction constructor.
     *
     * @param Agent $agent
     * @param Timer $timer
     */
    public function __construct(Agent $agent, Timer $timer)
    {
        $this->agent = $agent;
        $this->timer = $timer;
    }

    /**
     * @param Request $request
     * @param Closure $next
     *
     * @return Response
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            $transaction = $this->agent->startTransaction(
                $this->getTransactionName($request)
            );
        } catch (DuplicateTransactionNameException $e) {
        }

        // await the outcome
        $response = $next($request);

        $transaction->setResponse([
            'finished' => true,
            'headers_sent' => true,
            'status_code' => $response->getStatusCode(),
            'headers' => $this->formatHeaders($response->headers->all()),
        ]);

        $transaction->setUserContext([
            'id' => optional($request->user())->getAuthIdentifier(),
        ]);

        $transaction->setMeta([
            'result' => $response->getStatusCode(),
            'type' => 'HTTP'
        ]);

        $transaction->setSpans(app('query-log')->toArray());

        if (config('elastic-apm.transactions.use_route_uri')) {
            $transaction->setTransactionName($this->getRouteUriTransactionName($request));
        }

        try {
            $transaction->stop($this->timer->getElapsedInMilliseconds());
        } catch (NotStartedException $e) {
        }

        return $response;
    }

    /**
     * Perform any final actions for the request lifecycle.
     *
     * @param  Request $request
     * @param  Response $response
     *
     * @return void
     */
    public function terminate($request, $response)
    {
        try {
            $this->agent->send();
        }
        catch(\Throwable $t) {
            Log::error($t);
        }
    }

    /**
     * @param  Request $request
     *
     * @return string
     */
    protected function getTransactionName(Request $request): string
    {
        // fix leading /
        $path = ($request->server->get('REQUEST_URI') == '') ? '/' : $request->server->get('REQUEST_URI');

        return sprintf(
            "%s %s",
            $request->server->get('REQUEST_METHOD'),
            $path
        );
    }

    /**
     * @param  Request $request
     *
     * @return string
     */
    protected function getRouteUriTransactionName(Request $request): string
    {
        $path = ($request->path() === '/') ? '' : $request->path();

        return sprintf(
            "%s /%s",
            $request->server->get('REQUEST_METHOD'),
            $path
        );
    }

    /**
     * @param array $headers
     *
     * @return array
     */
    protected function formatHeaders(array $headers): array
    {
        return collect($headers)->map(function ($values, $header) {
            return head($values);
        })->toArray();
    }
}