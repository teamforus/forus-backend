<?php

namespace App\Services\IdentityProviderService\Responses;

use Illuminate\Http\JsonResponse;

class IdentityProviderScimResponse
{
    public const string CONTENT_TYPE = 'application/scim+json';
    public const string SCHEMA_LIST = 'urn:ietf:params:scim:api:messages:2.0:ListResponse';
    public const string SCHEMA_ERROR = 'urn:ietf:params:scim:api:messages:2.0:Error';

    /**
     * @param array $data
     * @param int $status
     * @param array $headers
     * @return JsonResponse
     */
    public static function json(array $data, int $status = 200, array $headers = []): JsonResponse
    {
        return new JsonResponse($data, $status, [
            ...$headers, 'Content-Type' => self::CONTENT_TYPE, 'Cache-Control' => 'no-store',
        ]);
    }

    /**
     * @param array $resources
     * @param int $total
     * @param int $startIndex
     * @return JsonResponse
     */
    public static function listing(array $resources, int $total, int $startIndex = 1): JsonResponse
    {
        return self::json([
            'schemas' => [self::SCHEMA_LIST],
            'totalResults' => $total,
            'startIndex' => $startIndex,
            'itemsPerPage' => count($resources),
            'Resources' => $resources,
        ]);
    }

    /**
     * @param int $status
     * @param string $detail
     * @param string|null $scimType
     * @param array $headers
     * @return JsonResponse
     */
    public static function error(
        int $status,
        string $detail,
        ?string $scimType = null,
        array $headers = [],
    ): JsonResponse {
        return self::json([
            'schemas' => [self::SCHEMA_ERROR],
            'status' => (string) $status,
            'detail' => $detail,
            ...$scimType ? ['scimType' => $scimType] : [],
        ], $status, $headers);
    }
}
