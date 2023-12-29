<?php


namespace App\Scopes\Builders;

use App\Services\BIConnectionService\Models\BIConnection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;

class BIConnectionQuery
{
    /**
     * @param BIConnection|Builder|Relation $query
     * @param Request $request
     * @param string $type
     * @return Builder|Relation
     */
    public static function whereValidToken(
        BIConnection|Builder|Relation $query,
        Request $request,
        string $type
    ): Builder|Relation {
        if ($type === BIConnection::AUTH_TYPE_PARAMETER) {
            $query->where('auth_type', BIConnection::AUTH_TYPE_PARAMETER)
                ->where('access_token', $request->get(BIConnection::AUTH_TYPE_PARAMETER_NAME));
        } else {
            $query->where('auth_type', BIConnection::AUTH_TYPE_HEADER)
                ->where('access_token', $request->header(BIConnection::AUTH_TYPE_HEADER_NAME));
        }

        return self::whereNotExpiredAndHasIp($query, $request->ip());
    }

    /**
     * @param BIConnection|Builder|Relation $query
     * @param string $ip
     * @return Relation|Builder
     */
    public static function whereNotExpiredAndHasIp(
        BIConnection|Builder|Relation $query,
        string $ip
    ): Relation|Builder {
        return $query->whereJsonContains('ips', $ip)->where('expire_at', '>', now());
    }
}