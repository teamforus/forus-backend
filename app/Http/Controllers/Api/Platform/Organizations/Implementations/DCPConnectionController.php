<?php

namespace App\Http\Controllers\Api\Platform\Organizations\Implementations;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Platform\Organizations\Implementations\DCPConnections\TestDCPConnectionRequest;
use App\Http\Requests\Api\Platform\Organizations\Implementations\DCPConnections\UpdateDCPConnectionRequest;
use App\Http\Resources\DCPConnectionResource;
use App\Models\Implementation;
use App\Models\Organization;
use App\Services\DCPService\Models\DCPConnection;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

class DCPConnectionController extends Controller
{
    /**
     * @param Organization $organization
     * @param Implementation $implementation
     * @throws AuthorizationException
     * @return DCPConnectionResource
     */
    public function show(Organization $organization, Implementation $implementation): DCPConnectionResource
    {
        $this->authorize('show', $organization);
        $this->authorize('manageDCPConnection', [$implementation, $organization]);

        if ($implementation->dcp_connection) {
            return DCPConnectionResource::create($implementation->dcp_connection);
        }

        return new DCPConnectionResource($implementation->dcp_connection()->make([
            'enabled' => false,
            'url' => Config::get('dcp.allowed_urls')[0] ?? null,
        ]));
    }

    /**
     * @param UpdateDCPConnectionRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @throws AuthorizationException
     * @return DCPConnectionResource
     */
    public function update(
        UpdateDCPConnectionRequest $request,
        Organization $organization,
        Implementation $implementation,
    ): DCPConnectionResource {
        $this->authorize('show', $organization);
        $this->authorize('manageDCPConnection', [$implementation, $organization]);

        return DCPConnectionResource::create(DCPConnection::saveForImplementation($implementation, $request->only([
            'enabled', 'url', 'username', 'password', 'order_types',
        ])));
    }

    /**
     * Options of the mapping: the DCP environments, the types of the orders with their source fields and the formats.
     *
     * @param Organization $organization
     * @param Implementation $implementation
     * @throws AuthorizationException
     * @return JsonResponse
     */
    public function options(Organization $organization, Implementation $implementation): JsonResponse
    {
        $this->authorize('show', $organization);
        $this->authorize('manageDCPConnection', [$implementation, $organization]);

        $types = DCPConnection::orderableTypes();

        return new JsonResponse(['data' => [
            'urls' => Config::get('dcp.allowed_urls', []),
            'orderable_types' => array_map(fn (string $type, string $class) => [
                'key' => $type,
                'name' => trans("dcp.orderable_types.$type"),
                'source_fields' => array_map(fn (string $key, string $name) => [
                    'key' => $key,
                    'name' => $name,
                ], array_keys($class::dcpOrderSourceFields()), $class::dcpOrderSourceFields()),
            ], array_keys($types), $types),
            'formats' => array_map(fn (string $format) => [
                'key' => $format,
                'name' => Str::startsWith($format, 'date:')
                    ? trans('dcp.formats.date', ['format' => Str::after($format, 'date:')])
                    : trans("dcp.formats.$format"),
            ], Config::get('dcp.formats', [])),
        ]]);
    }

    /**
     * @param TestDCPConnectionRequest $request
     * @param Organization $organization
     * @param Implementation $implementation
     * @throws AuthorizationException
     * @return JsonResponse
     */
    public function test(
        TestDCPConnectionRequest $request,
        Organization $organization,
        Implementation $implementation,
    ): JsonResponse {
        $this->authorize('show', $organization);
        $this->authorize('manageDCPConnection', [$implementation, $organization]);

        $saved = $implementation->dcp_connection;

        $connection = new DCPConnection([
            'url' => $request->input('url') ?: $saved?->url,
            'username' => $request->input('username') ?: $saved?->username,
            'password' => $request->input('password') ?: $saved?->password,
        ]);

        if (!$connection->hasCredentials()) {
            return new JsonResponse(['success' => false, 'message' => trans('dcp.connection_test.missing')], 422);
        }

        [$success, $message] = $connection->testConnection();

        return new JsonResponse(['success' => $success, 'message' => $message]);
    }
}
